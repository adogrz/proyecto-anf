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
        $errores = [];
        $warnings = [];
        $previewData = [];

        try {
            $import = new class implements WithHeadingRow {};
            $filas = Excel::toArray($import, $file)[0] ?? [];

            if (empty($filas)) {
                $errores[] = "El archivo está vacío o no se pudo leer.";
                return ['datos' => [], 'errores' => $errores, 'warnings' => $warnings];
            }

            // Obtener el catálogo de cuentas de la empresa para mapear
            $catalogoEmpresa = CatalogoCuenta::where('empresa_id', $empresaId)
                ->get()
                ->keyBy('codigo_cuenta');

            $numeroFila = 1; // Start from 1 for user-friendly row numbering
            foreach ($filas as $fila) {
                $numeroFila++; // Increment for each row

                // Normalize keys to lowercase and replace spaces/hyphens with underscores
                $filaNorm = $this->normalizeRowKeys($fila);

                $codigoCuenta = trim((string)($filaNorm['codigo_cuenta'] ?? $filaNorm['codigo'] ?? ''));
                $saldo = (float)($filaNorm['saldo'] ?? $filaNorm['valor'] ?? 0);

                if (empty($codigoCuenta)) {
                    $errores[] = "Fila {$numeroFila}: El código de cuenta es obligatorio.";
                    continue;
                }

                // Check if the account exists in the company's catalog
                $cuentaCatalogo = $catalogoEmpresa->get($codigoCuenta);

                if (!$cuentaCatalogo) {
                    $warnings[] = "Fila {$numeroFila}: La cuenta '{$codigoCuenta}' no existe en el catálogo de la empresa. Será ignorada.";
                    continue; // Skip this row if not in catalog
                }

                // Specific validations based on tipoEstado
                if ($tipoEstado === 'balance_general') {
                    $fecha = trim((string)($filaNorm['fecha'] ?? $filaNorm['date'] ?? ''));
                    if (empty($fecha)) {
                        $errores[] = "Fila {$numeroFila}: La fecha es obligatoria para Balance General.";
                        continue;
                    }
                    // Basic date format validation (YYYY-MM-DD)
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                        $errores[] = "Fila {$numeroFila}: Formato de fecha inválido para Balance General. Se espera YYYY-MM-DD.";
                        continue;
                    }
                    // Ensure date is within the specified year
                    if (date('Y', strtotime($fecha)) != $anio) {
                        $errores[] = "Fila {$numeroFila}: La fecha '{$fecha}' no corresponde al año {$anio} especificado.";
                        continue;
                    }
                } elseif ($tipoEstado === 'estado_resultados') {
                    $periodo = trim((string)($filaNorm['periodo'] ?? $filaNorm['month'] ?? ''));
                    if (empty($periodo)) {
                        $errores[] = "Fila {$numeroFila}: El período es obligatorio para Estado de Resultados.";
                        continue;
                    }
                    // Basic period format validation (YYYY-MM)
                    if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                        $errores[] = "Fila {$numeroFila}: Formato de período inválido para Estado de Resultados. Se espera YYYY-MM.";
                        continue;
                    }
                    // Ensure period is within the specified year
                    if (substr($periodo, 0, 4) != $anio) {
                        $errores[] = "Fila {$numeroFila}: El período '{$periodo}' no corresponde al año {$anio} especificado.";
                        continue;
                    }
                }

                $previewData[] = [
                    'codigo_cuenta' => $codigoCuenta,
                    'nombre_cuenta' => $cuentaCatalogo->nombre_cuenta,
                    'cuenta_base_id' => $cuentaCatalogo->cuenta_base_id,
                    'cuenta_base_nombre' => $cuentaCatalogo->cuentaBase->nombre ?? null,
                    'saldo' => $saldo,
                    'fecha' => $fecha ?? null, // Only for balance_general
                    'periodo' => $periodo ?? null, // Only for estado_resultados
                ];
            }

        } catch (\Exception $e) {
            Log::error("Error en previsualizar Estado Financiero: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $errores[] = "Error al procesar el archivo: " . $e->getMessage();
        }

        return ['datos' => $previewData, 'errores' => $errores, 'warnings' => $warnings];
    }

    public function guardar(int $empresaId, int $anio, string $tipoEstado, array $detalles): void
    {
        DB::transaction(function () use ($empresaId, $anio, $tipoEstado, $detalles) {
            // Delete existing financial statement for the given year and type
            EstadoFinanciero::where('empresa_id', $empresaId)
                ->where('anio', $anio)
                ->where('tipo_estado', $tipoEstado)
                ->delete();

            $estadoFinanciero = EstadoFinanciero::create([
                'empresa_id' => $empresaId,
                'anio' => $anio,
                'tipo_estado' => $tipoEstado,
            ]);

            foreach ($detalles as $detalle) {
                DetalleEstado::create([
                    'estado_financiero_id' => $estadoFinanciero->id,
                    'codigo_cuenta' => $detalle['codigo_cuenta'],
                    'saldo' => $detalle['saldo'],
                    'fecha' => $detalle['fecha'] ?? null,
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
