<?php

namespace App\Services;

use App\Models\CatalogoCuenta;
use App\Models\DetalleEstado;
use App\Models\EstadoFinanciero;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class EstadoFinancieroService
{
    protected $calculoRatiosService;

    public function __construct(CalculoRatiosService $calculoRatiosService)
    {
        $this->calculoRatiosService = $calculoRatiosService;
    }

    public function previsualizar(UploadedFile $file, int $empresaId, int $anio, string $tipoEstado): array
    {
        $erroresGlobales = []; // For errors affecting the whole file
        $warningsGlobales = []; // For warnings affecting the whole file
        $previewData = []; // Each item will now include status and row_errors

        try {
            $filas = [];
            $extension = strtolower($file->getClientOriginalExtension());

            if ($extension === 'csv') {
                // Try parsing with comma delimiter first (standard for new templates)
                $importComma = new class implements WithHeadingRow, WithCustomCsvSettings {
                    public function getCsvSettings(): array
                    {
                        return ['delimiter' => ','];
                    }
                };
                $filas = Excel::toArray($importComma, $file)[0] ?? [];

                // If parsing with comma yields no data or a single column (bad parse), try with semicolon
                if (empty($filas) || (count($filas) > 0 && count($filas[0]) <= 1)) {
                    $importSemicolon = new class implements WithHeadingRow, WithCustomCsvSettings {
                        public function getCsvSettings(): array
                        {
                            return ['delimiter' => ';'];
                        }
                    };
                    $filas = Excel::toArray($importSemicolon, $file)[0] ?? [];
                }
            } else {
                // For XLSX, XLS, etc., use default import
                $import = new class implements WithHeadingRow {};
                $filas = Excel::toArray($import, $file)[0] ?? [];
            }

            if (empty($filas)) {
                $erroresGlobales[] = "El archivo está vacío o no se pudo leer. Verifique el formato y el delimitador (coma o punto y coma).";
                return ['datos' => [], 'errores' => $erroresGlobales, 'warnings' => $warningsGlobales];
            }

            // Get the company's plantilla_catalogo_id
            $empresa = \App\Models\Empresa::find($empresaId);
            if (!$empresa || !$empresa->plantilla_catalogo_id) {
                $erroresGlobales[] = "La empresa no tiene una plantilla de catálogo asociada.";
                return ['datos' => [], 'errores' => $erroresGlobales, 'warnings' => $warningsGlobales];
            }

            // Get the CuentaBase records associated with the company's plantilla
            $cuentasBasePlantilla = \App\Models\CuentaBase::where('plantilla_catalogo_id', $empresa->plantilla_catalogo_id)
                ->get()
                ->keyBy('codigo');

            // --- 1. Initial Header Check ---
            $firstRow = $filas[0];
            $normalizedHeaders = array_keys($this->normalizeRowKeys($firstRow));

            $expectedHeaders = ['codigo_cuenta', 'saldo'];

            $missingHeaders = array_diff($expectedHeaders, $normalizedHeaders);

            if (!empty($missingHeaders)) {
                $erroresGlobales[] = "Faltan columnas obligatorias en el archivo: " . implode(', ', $missingHeaders) . ". Por favor, use la plantilla de descarga.";
                return ['datos' => [], 'errores' => $erroresGlobales, 'warnings' => $warningsGlobales];
            }
            // --- End Initial Header Check ---

            $numeroFila = 1; // Start from 1 for user-friendly row numbering (assuming first row is headers)
            foreach ($filas as $fila) {
                $numeroFila++; // Increment for each data row
                $rowErrors = [];
                $rowWarnings = [];
                $rowStatus = 'valid';

                $filaNorm = $this->normalizeRowKeys($fila);
                
                // Debug: Log first row to see structure
                if ($numeroFila === 2) {
                    Log::info("🔍 Debug primera fila normalizada:", ['fila_original' => $fila, 'fila_normalizada' => $filaNorm]);
                }

                // Convertir código de cuenta a string y limpiar (puede venir como número decimal del CSV)
                $codigoCuenta = trim((string)($filaNorm['codigo_cuenta'] ?? $filaNorm['codigo'] ?? ''));
                
                // Si el código viene como número decimal (ej: 1101.01), asegurarnos de que mantenga el formato
                // Excel a veces lee "1101.01" como número 1101.01
                if (is_numeric($codigoCuenta) && str_contains($codigoCuenta, '.')) {
                    // Mantener el formato decimal si tiene punto
                    $codigoCuenta = rtrim(rtrim($codigoCuenta, '0'), '.');
                }
                
                $saldoRaw = $filaNorm['saldo'] ?? $filaNorm['valor'] ?? null; // Keep raw for validation

                // --- Validation for codigo_cuenta ---
                if (empty($codigoCuenta)) {
                    $rowErrors[] = "El código de cuenta es obligatorio.";
                    $rowStatus = 'error';
                }

                $cuentaBase = null;
                if ($rowStatus !== 'error') { // Only check if no prior errors for this field
                    $cuentaBase = $cuentasBasePlantilla->get($codigoCuenta);
                    if (!$cuentaBase) {
                        // Cambio: Ya no es warning, permitimos que se importe
                        // La cuenta se creará automáticamente durante el guardado
                        Log::info("📋 La cuenta '{$codigoCuenta}' no existe en la plantilla pero se creará automáticamente al importar.");
                        // No agregamos warning, solo dejamos que continúe
                    }
                }

                // --- Validation for saldo ---
                $saldo = 0.0;
                if ($saldoRaw === null || $saldoRaw === '') {
                    $rowErrors[] = "El monto (saldo) es obligatorio.";
                    $rowStatus = 'error';
                } else {
                    // Attempt to normalize decimal separator
                    $normalizedSaldo = (string)$saldoRaw;
                    if (str_contains($normalizedSaldo, ',') && (!str_contains($normalizedSaldo, '.') || strrpos($normalizedSaldo, ',') > strrpos($normalizedSaldo, '.'))) {
                        $normalizedSaldo = str_replace('.', '', $normalizedSaldo);
                        $normalizedSaldo = str_replace(',', '.', $normalizedSaldo);
                    } else {
                        $normalizedSaldo = str_replace(',', '', $normalizedSaldo);
                    }

                    if (!is_numeric($normalizedSaldo)) {
                        $rowErrors[] = "El monto (saldo) debe ser un valor numérico válido.";
                        $rowStatus = 'error';
                    } else {
                        $saldo = (float)$normalizedSaldo;
                    }
                }

                // Derive fecha and periodo based on tipoEstado and anio
                $periodo = null;
                $fecha = null;

                if ($tipoEstado === 'balance_general') {
                    $fecha = "{$anio}-12-31"; // Assuming year-end balance
                } elseif ($tipoEstado === 'estado_resultados') {
                    $periodo = "{$anio}-12"; // Assuming annual income statement for the whole year
                }

                // Add row to previewData with its status and errors/warnings
                $previewData[] = [
                    'original_row_data' => $fila,
                    'codigo_cuenta' => $codigoCuenta,
                    'nombre_cuenta' => $cuentaBase->nombre ?? ('Cuenta ' . $codigoCuenta),
                    'cuenta_base_id' => $cuentaBase->id ?? null,
                    'cuenta_base_nombre' => $cuentaBase->nombre ?? ('Cuenta ' . $codigoCuenta),
                    'saldo' => $saldo,
                    'fecha' => $fecha,
                    'periodo' => $periodo,
                    'status' => $rowStatus,
                    'row_errors' => $rowErrors,
                    'row_warnings' => $rowWarnings,
                ];

                if ($rowStatus === 'error') {
                    $erroresGlobales = array_merge($erroresGlobales, $rowErrors);
                }
                if ($rowStatus === 'warning') {
                    $warningsGlobales = array_merge($warningsGlobales, $rowWarnings);
                }
            }

        } catch (\Exception $e) {
            Log::error("Error en previsualizar Estado Financiero: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $erroresGlobales[] = "Error al procesar el archivo: " . $e->getMessage();
        }

        $finalDatos = [];
        if (empty($erroresGlobales)) {
            foreach ($previewData as $item) {
                $finalDatos[] = $item;
            }
        }

        Log::info("📊 Resultado de previsualización:", [
            'total_previewData' => count($previewData),
            'total_finalDatos' => count($finalDatos),
            'total_errores' => count($erroresGlobales),
            'total_warnings' => count($warningsGlobales),
            'errores' => $erroresGlobales,
            'warnings' => $warningsGlobales
        ]);

        return ['datos' => $finalDatos, 'errores' => $erroresGlobales, 'warnings' => $warningsGlobales];
    }

    public function guardar(int $empresaId, int $anio, string $tipoEstado, array $detalles): void
    {
        DB::transaction(function () use ($empresaId, $anio, $tipoEstado, $detalles) {
            // Delete existing financial statement for the given year and type to prevent duplicates
            $estadoFinancieroExistente = EstadoFinanciero::where('empresa_id', $empresaId)
                ->where('anio', $anio)
                ->where('tipo_estado', $tipoEstado)
                ->first();

            if ($estadoFinancieroExistente) {
                $estadoFinancieroExistente->detalles()->delete();
                $estadoFinancieroExistente->delete();
            }

            $estadoFinanciero = EstadoFinanciero::create([
                'empresa_id' => $empresaId,
                'anio' => $anio,
                'tipo_estado' => $tipoEstado,
            ]);

            foreach ($detalles as $index => $detalle) {
                $fechaParaGuardar = null;
                $periodoParaGuardar = null;

                if ($tipoEstado === 'balance_general') {
                    $fechaParaGuardar = "{$anio}-12-31";
                } elseif ($tipoEstado === 'estado_resultados') {
                    $periodoParaGuardar = "{$anio}-12"; // Assuming annual income statement for the whole year
                }

                // Find the CatalogoCuenta for the given empresa_id and codigo_cuenta from the uploaded file
                $catalogoCuenta = \App\Models\CatalogoCuenta::where('empresa_id', $empresaId)
                                                            ->where('codigo_cuenta', $detalle['codigo_cuenta'])
                                                            ->first();

                // Si la cuenta no existe en el catálogo, crearla automáticamente
                if (!$catalogoCuenta) {
                    Log::info("📝 Cuenta '{$detalle['codigo_cuenta']}' no existe en el catálogo. Creándola automáticamente...");
                    
                    // Buscar la cuenta base para obtener información adicional
                    $cuentaBase = \App\Models\CuentaBase::where('codigo', $detalle['codigo_cuenta'])->first();
                    
                    if (!$cuentaBase) {
                        // Si tampoco existe en cuentas base, crear con datos mínimos
                        Log::warning("⚠️ Cuenta '{$detalle['codigo_cuenta']}' tampoco existe en cuentas base. Creando con nombre del archivo.");
                        
                        $catalogoCuenta = \App\Models\CatalogoCuenta::create([
                            'empresa_id' => $empresaId,
                            'codigo_cuenta' => $detalle['codigo_cuenta'],
                            'nombre_cuenta' => $detalle['nombre_cuenta'] ?? 'Cuenta importada - ' . $detalle['codigo_cuenta'],
                            'cuenta_base_id' => null,
                        ]);
                    } else {
                        // Crear con la información de cuenta base
                        $catalogoCuenta = \App\Models\CatalogoCuenta::create([
                            'empresa_id' => $empresaId,
                            'codigo_cuenta' => $detalle['codigo_cuenta'],
                            'nombre_cuenta' => $cuentaBase->nombre,
                            'cuenta_base_id' => $cuentaBase->id,
                        ]);
                        
                        Log::info("✅ Cuenta '{$detalle['codigo_cuenta']}' creada en el catálogo desde cuenta base.");
                    }
                }

                DetalleEstado::create([
                    'estado_financiero_id' => $estadoFinanciero->id,
                    'catalogo_cuenta_id' => $catalogoCuenta->id, // Use catalogo_cuenta_id
                    'codigo_cuenta' => $detalle['codigo_cuenta'], // This is the company's specific code
                    'valor' => $detalle['saldo'], // Use 'valor' instead of 'saldo'
                    'fecha' => $fechaParaGuardar,
                    'periodo' => $periodoParaGuardar,
                ]);
            }
        });

        // Después de guardar exitosamente el estado financiero, calcular ratios
        // Los ratios se calcularán incluso si no existen todas las cuentas necesarias
        try {
            Log::info("🔢 Calculando ratios para empresa {$empresaId}, año {$anio} después de importar estado financiero.");
            
            $this->calculoRatiosService->calcularYGuardar($empresaId, $anio);
            
            Log::info("✅ Ratios calculados exitosamente para empresa {$empresaId}, año {$anio}.");
        } catch (\Exception $e) {
            // No lanzamos la excepción para no afectar el guardado del estado financiero
            // pero sí registramos el error
            Log::warning("⚠️ Error al calcular ratios para empresa {$empresaId}, año {$anio}: " . $e->getMessage());
        }
    }

    private function normalizeRowKeys(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            if (is_null($k)) {
                continue;
            }
            $key = strtolower(trim((string)$k));
            // Remove potential UTF-8 BOM from the first key
            $key = preg_replace('/^\x{FEFF}/u', '', $key);
            $key = preg_replace('/[\s\-]+/', '_', $key);
            $out[$key] = $v;
        }
        return $out;
    }
}
