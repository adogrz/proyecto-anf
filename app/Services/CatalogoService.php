<?php

namespace App\Services;

use App\Models\CatalogoCuenta;
use App\Models\CuentaBase;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Illuminate\Support\Facades\Log;

class CatalogoService
{
    public function procesarAutomap(string $filePath, int $plantillaId): array
    {
        // Fix: 'custom' no existe en HeadingRowFormatter -> usar 'none' o eliminar.
        // Usamos 'none' para preservar las cabeceras tal cual y normalizarlas después.
        HeadingRowFormatter::default('none');

        $errores = [];

        try {
            if (!file_exists($filePath)) {
                throw new \Exception("Archivo no encontrado: {$filePath}");
            }

            $import = new class implements WithHeadingRow {};
            $filas = Excel::toArray($import, $filePath)[0] ?? [];

            if (empty($filas)) {
                throw new \Exception("Archivo vacío o no se pudo leer.");
            }

            // Obtener cuentas base indexadas por código (según migración: 'codigo')
            $cuentasBase = CuentaBase::where('plantilla_catalogo_id', $plantillaId)
                ->get()
                ->keyBy('codigo');

            $mapeoResultado = [];
            $codigosUnicos = [];

            foreach ($filas as $index => $fila) {
                // Normalizar cabeceras y valores para tolerar variantes
                $filaNorm = $this->normalizeRowKeys($fila);

                $resultado = $this->procesarFila($filaNorm, $index + 2, $cuentasBase, $codigosUnicos);

                if (isset($resultado['error'])) {
                    $errores[] = $resultado['error'];
                    continue;
                }

                $codigosUnicos[] = $resultado['datos']['codigo'];
                $mapeoResultado[] = $resultado['datos'];
            }

            return [
                'datos' => $mapeoResultado,
                'errores' => $errores,
                'stats' => [
                    'total' => count($mapeoResultado),
                    'mapeadas' => count(array_filter($mapeoResultado, fn($item) => !is_null($item['cuenta_base_id']))),
                    'sin_mapear' => count(array_filter($mapeoResultado, fn($item) => is_null($item['cuenta_base_id'])))
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Error en procesarAutomap: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
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

    private function procesarFila(array $fila, int $numeroFila, $cuentasBase, array $codigosUnicos): array
    {
        // Soporta variantes de nombre de columna
        $codigo = trim((string)($fila['codigo'] ?? $fila['codigo_cuenta'] ?? $fila['code'] ?? ''));
        $nombre = trim((string)($fila['nombre'] ?? $fila['nombre_cuenta'] ?? $fila['account_name'] ?? ''));
        $tipoCuenta = strtoupper(trim((string)($fila['tipo_cuenta'] ?? $fila['tipo'] ?? '')));
        $naturaleza = strtoupper(trim((string)($fila['naturaleza'] ?? $fila['nature'] ?? $fila['nat'] ?? '')));

        if ($codigo === '' || $nombre === '') {
            return ['error' => "Fila {$numeroFila}: 'codigo' y 'nombre' son obligatorios."];
        }

        if (in_array($codigo, $codigosUnicos, true)) {
            return ['error' => "Fila {$numeroFila}: Código duplicado '{$codigo}'."];
        }

        // Normalizar valores de tipo/naturaleza aceptados por la migración
        if ($tipoCuenta === '') {
            // asumir DETALLE si tiene subcódigos, AGRUPACION si termina en .0 o sin subniveles
            $tipoCuenta = (strpos($codigo, '.') !== false) ? 'DETALLE' : 'AGRUPACION';
        } else {
            $mapTipo = ['AGRUPACION' => 'AGRUPACION', 'AGRUP.' => 'AGRUPACION', 'DETALLE' => 'DETALLE', 'DET.' => 'DETALLE'];
            $tipoCuenta = $mapTipo[$tipoCuenta] ?? $tipoCuenta;
        }

        if ($naturaleza === '') {
            // intentar inferir por rango de código (simple heurística) - no perfecta
            $naturaleza = 'DEUDORA';
        } else {
            $mapNat = ['D' => 'DEUDORA', 'H' => 'ACREEDORA', 'DEUDORA' => 'DEUDORA', 'ACREEDORA' => 'ACREEDORA'];
            $naturaleza = $mapNat[$naturaleza] ?? $naturaleza;
        }

        $cuentaBase = $cuentasBase->get($codigo);

        return ['datos' => [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'tipo_cuenta' => $tipoCuenta,
            'naturaleza' => $naturaleza,
            'cuenta_base_id' => $cuentaBase ? $cuentaBase->id : null,
            'cuenta_base_nombre' => $cuentaBase ? $cuentaBase->nombre : null,
        ]];
    }

    public function guardarMapeo(array $validatedData): void
    {
        $empresaId = $validatedData['empresa_id'];

        // Limpiar el catálogo anterior para esta empresa
        CatalogoCuenta::where('empresa_id', $empresaId)->delete();

        // Crear las nuevas cuentas del catálogo
        foreach ($validatedData['cuentas'] as $cuenta) {
            CatalogoCuenta::create([
                'empresa_id' => $empresaId,
                'codigo_cuenta' => $cuenta['codigo_cuenta'],
                'nombre_cuenta' => $cuenta['nombre_cuenta'],
                'cuenta_base_id' => $cuenta['cuenta_base_id'],
            ]);
        }
    }

    public function importarCuentasBase(\Illuminate\Http\UploadedFile $file, int $plantillaCatalogoId): void
    {
        $collection = Excel::toCollection(new \App\Imports\CuentasBaseImport, $file)->first();

        \Illuminate\Support\Facades\DB::transaction(function () use ($collection, $plantillaCatalogoId) {
            // 1. Prepare data and identify parents
            $accountsData = [];
            $parentCodes = [];
            foreach ($collection as $row) {
                if (empty($row[0]) || empty($row[1])) {
                    continue;
                }

                $codigo = (string) $row[0];
                $nombre = (string) $row[1];

                // Determine parent code
                $parentCode = null;
                if (str_contains($codigo, '.')) {
                    $parts = explode('.', $codigo);
                    array_pop($parts);
                    $parentCode = implode('.', $parts);
                } elseif (strlen($codigo) > 2) {
                    $parentCode = substr($codigo, 0, -2);
                } elseif (strlen($codigo) > 1) {
                     $parentCode = substr($codigo, 0, -1);
                }


                if ($parentCode) {
                    $parentCodes[$parentCode] = true;
                }

                // Determine nature
                $naturaleza = 'DEUDORA'; // Default
                $firstDigit = substr($codigo, 0, 1);
                if (in_array($firstDigit, ['2', '3', '5'])) {
                    $naturaleza = 'ACREEDORA';
                }

                $accountsData[] = [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'naturaleza' => $naturaleza,
                    'parent_code' => $parentCode,
                    'plantilla_catalogo_id' => $plantillaCatalogoId,
                ];
            }

            // 2. Determine account type and prepare for insertion
            $accountsToInsert = [];
            foreach ($accountsData as $account) {
                $accountsToInsert[] = [
                    'plantilla_catalogo_id' => $account['plantilla_catalogo_id'],
                    'parent_id' => null, // Set later
                    'codigo' => $account['codigo'],
                    'nombre' => $account['nombre'],
                    'tipo_cuenta' => isset($parentCodes[$account['codigo']]) ? 'AGRUPACION' : 'DETALLE',
                    'naturaleza' => $account['naturaleza'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 3. Clear old accounts and insert new ones
            CuentaBase::where('plantilla_catalogo_id', $plantillaCatalogoId)->delete();
            CuentaBase::insert($accountsToInsert);

            // 4. Create a code -> id map
            $codeIdMap = CuentaBase::where('plantilla_catalogo_id', $plantillaCatalogoId)
                ->pluck('id', 'codigo');

            // 5. Update parent_id
            foreach ($accountsData as $account) {
                if ($account['parent_code'] && isset($codeIdMap[$account['codigo']]) && isset($codeIdMap[$account['parent_code']])) {
                    $id = $codeIdMap[$account['codigo']];
                    $parentId = $codeIdMap[$account['parent_code']];

                    CuentaBase::where('id', $id)->update(['parent_id' => $parentId]);
                }
            }
        });
    }

    public function validarPrevisualizacion(array $datos): array
    {
        $errores = [];
        $warnings = [];
        
        // Validaciones de negocio
        $totalCuentas = count($datos);
        $cuentasMapeadas = count(array_filter($datos, fn($item) => !is_null($item['cuenta_base_id'])));
        $porcentajeMapeado = ($totalCuentas > 0) ? ($cuentasMapeadas / $totalCuentas) * 100 : 0;

        if ($porcentajeMapeado < 50) {
            $warnings[] = "Solo se ha podido mapear el " . round($porcentajeMapeado, 2) . "% de las cuentas.";
        }

        // Validar estructura jerárquica
        $codigosPadre = [];
        foreach ($datos as $cuenta) {
            $codigo = $cuenta['codigo_cuenta'];
            if (str_contains($codigo, '.')) {
                $padre = substr($codigo, 0, strrpos($codigo, '.'));
                if (!in_array($padre, array_column($datos, 'codigo_cuenta'))) {
                    $errores[] = "La cuenta {$codigo} hace referencia a un padre {$padre} que no existe.";
                }
            }
            $codigosPadre[] = $codigo;
        }

        return [
            'errores' => $errores,
            'warnings' => $warnings,
            'stats' => [
                'total' => $totalCuentas,
                'mapeadas' => $cuentasMapeadas,
                'porcentaje' => round($porcentajeMapeado, 2)
            ]
        ];
    }

    private function determinarNaturalezaCuenta(string $codigo): string 
    {
        // Obtener el primer dígito del código
        $firstDigit = substr($codigo, 0, 1);
        
        return match($firstDigit) {
            '1', '4', '5', '6' => 'DEUDORA',    // Activos, Gastos, Costos
            '2', '3' => 'ACREEDORA',  // Pasivos, Capital
            default => 'DEUDORA'      // Por defecto
        };
    }

    private function determinarTipoCuenta(string $codigo): string 
    {
        // Si tiene puntos o más de 2 dígitos es cuenta de detalle
        return (str_contains($codigo, '.') || strlen($codigo) > 2) 
            ? 'DETALLE' 
            : 'AGRUPACION';
    }
}