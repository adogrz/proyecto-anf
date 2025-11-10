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

class EstadoFinancieroService
{
    public function previsualizar(UploadedFile $file, int $empresaId, int $anio, string $tipoEstado): array
    {
        $erroresGlobales = []; // For errors affecting the whole file
        $warningsGlobales = []; // For warnings affecting the whole file
        $previewData = []; // Each item will now include status and row_errors

        try {
            $import = new class implements WithHeadingRow {};
            $filas = Excel::toArray($import, $file)[0] ?? [];

            if (empty($filas)) {
                $erroresGlobales[] = "El archivo está vacío o no se pudo leer.";
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
            // Ensure there's at least one row to get headers from
            if (empty($filas)) {
                $erroresGlobales[] = "El archivo está vacío o no contiene datos.";
                return ['datos' => [], 'errores' => $erroresGlobales, 'warnings' => $warningsGlobales];
            }
            
            $firstRow = $filas[0];
            $normalizedHeaders = array_keys($this->normalizeRowKeys($firstRow));

            $expectedHeaders = ['codigo_cuenta', 'saldo'];
            if ($tipoEstado === 'estado_resultados') {
                $expectedHeaders[] = 'periodo';
            }
            // For balance general, 'fecha' is derived, so not a strict required header from file

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

                $codigoCuenta = trim((string)($filaNorm['codigo_cuenta'] ?? $filaNorm['codigo'] ?? ''));
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
                        $rowWarnings[] = "La cuenta '{$codigoCuenta}' no existe en las cuentas base de la plantilla de la empresa.";
                        if ($rowStatus === 'valid') $rowStatus = 'warning'; // Only downgrade from valid to warning
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
                    // Check for comma as decimal separator (e.g., "1.234,56" or "123,45")
                    // If it contains a comma AND the last comma is after any dot, or no dot exists
                    if (str_contains($normalizedSaldo, ',') && (!str_contains($normalizedSaldo, '.') || strrpos($normalizedSaldo, ',') > strrpos($normalizedSaldo, '.'))) {
                        $normalizedSaldo = str_replace('.', '', $normalizedSaldo); // Remove thousands separator (dot)
                        $normalizedSaldo = str_replace(',', '.', $normalizedSaldo); // Replace comma with dot for decimal
                    } else {
                        // Assume dot is decimal separator, remove commas if present (thousands separator)
                        $normalizedSaldo = str_replace(',', '', $normalizedSaldo);
                    }

                    if (!is_numeric($normalizedSaldo)) {
                        $rowErrors[] = "El monto (saldo) debe ser un valor numérico válido.";
                        $rowStatus = 'error';
                    } else {
                        $saldo = (float)$normalizedSaldo;
                    }
                }

                $periodo = null;
                $fecha = null;

                // --- Specific validations based on tipoEstado ---
                if ($tipoEstado === 'estado_resultados') {
                    $periodo = trim((string)($filaNorm['periodo'] ?? $filaNorm['month'] ?? ''));
                    if (empty($periodo)) {
                        $rowErrors[] = "El período es obligatorio para Estado de Resultados.";
                        $rowStatus = 'error';
                    } elseif (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                        $rowErrors[] = "Formato de período inválido para Estado de Resultados. Se espera YYYY-MM.";
                        $rowStatus = 'error';
                    } elseif (substr($periodo, 0, 4) != $anio) {
                        $rowErrors[] = "El período '{$periodo}' no corresponde al año {$anio} especificado.";
                        $rowStatus = 'error';
                    }
                }

                // Add row to previewData with its status and errors/warnings
                $previewData[] = [
                    'original_row_data' => $fila, // Keep original row for reference if needed
                    'codigo_cuenta' => $codigoCuenta,
                    'nombre_cuenta' => $cuentaBase->nombre ?? 'N/A', // Use N/A if cuentaBase not found or error
                    'cuenta_base_id' => $cuentaBase->id ?? null,
                    'cuenta_base_nombre' => $cuentaBase->nombre ?? 'N/A',
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

        // Filter out valid data if there are global errors, or if no valid data was found
        // If there are global errors, we return an empty 'datos' array, but still show global errors/warnings
        // If there are only row-level errors/warnings, we return all rows with their statuses
        $finalDatos = [];
        if (empty($erroresGlobales)) { // Only include data if no critical global errors
            foreach ($previewData as $item) {
                $finalDatos[] = $item; // Include all rows, even those with warnings/errors, for detailed preview
            }
        }

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

            foreach ($detalles as $detalle) {
                $fechaParaGuardar = null;
                if ($tipoEstado === 'balance_general') {
                    $fechaParaGuardar = "{$anio}-12-31";
                }

                DetalleEstado::create([
                    'estado_financiero_id' => $estadoFinanciero->id,
                    'cuenta_base_id' => $detalle['cuenta_base_id'],
                    'codigo_cuenta' => $detalle['codigo_cuenta'],
                    'saldo' => $detalle['saldo'],
                    'fecha' => $fechaParaGuardar,
                    'periodo' => $detalle['periodo'] ?? null,
                ]);
            }
        });
    }

    private function normalizeRowKeys(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $key = strtolower(trim((string)$k));
            $key = preg_replace('/[\s\-]+/', '_', $key);
            $out[$key] = $v;
        }
        return $out;
    }
}
