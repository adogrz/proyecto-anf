<?php

namespace App\Services;

use App\Models\CatalogoCuenta;
use App\Models\CuentaBase;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use Illuminate\Support\Facades\Log;

class CatalogoService
{
    public function procesarAutomap(string $filePath, int $plantillaId): array
    {
        $errores = [];
        $warnings = [];

        try {
            if (!file_exists($filePath)) {
                throw new \Exception("Archivo no encontrado: {$filePath}");
            }

            $import = new class implements WithHeadingRow {};
            $filas = Excel::toArray($import, $filePath)[0] ?? [];

            if (empty($filas)) {
                throw new \Exception("Archivo vacío o no se pudo leer.");
            }

            $cuentasBase = CuentaBase::where('plantilla_catalogo_id', $plantillaId)->get();
            $mapeoResultado = [];
            $codigosUnicos = [];

            foreach ($filas as $index => $fila) {
                $filaNorm = $this->normalizeRowKeys($fila);
                $resultado = $this->procesarFila($filaNorm, $index + 2, $cuentasBase, $codigosUnicos);

                if (isset($resultado['error'])) {
                    $errores[] = $resultado['error'];
                    continue;
                }
                
                if (isset($resultado['warning'])) {
                    $warnings[] = $resultado['warning'];
                    continue;
                }
                
                if (isset($resultado['datos'])) {
                    $mapeoResultado[] = $resultado['datos'];
                }
            }

            return [
                'datos' => $mapeoResultado,
                'errores' => $errores,
                'warnings' => $warnings,
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

    private function procesarFila(array $fila, int $numeroFila, $cuentasBase, array &$codigosUnicos): array
    {
        // Soporta variantes de nombre de columna
        $codigo = trim((string)($fila['codigo'] ?? $fila['codigo_cuenta'] ?? $fila['code'] ?? ''));
        $nombre = trim((string)($fila['nombre'] ?? $fila['nombre_cuenta'] ?? $fila['account_name'] ?? ''));
        
        if ($codigo === '' || $nombre === '') {
            return ['error' => "Fila {$numeroFila}: 'codigo_cuenta' y 'nombre_cuenta' son obligatorios."];
        }

        if (isset($codigosUnicos[$codigo])) {
            return ['warning' => "Fila {$numeroFila}: Código de cuenta '{$codigo}' duplicado. Se omitirá esta fila."];
        }
        $codigosUnicos[$codigo] = true;

        // Lógica de mapeo mejorada
        $cuentaBase = $this->findBestMatch($codigo, $nombre, $cuentasBase);

        return ['datos' => [
            'codigo_cuenta' => $codigo,
            'nombre_cuenta' => $nombre,
            'cuenta_base_id' => $cuentaBase ? $cuentaBase->id : null,
            'cuenta_base_nombre' => $cuentaBase ? $cuentaBase->nombre : null,
        ]];
    }

    public function guardarMapeo(array $validatedData): void
    {
        $empresaId = $validatedData['empresa_id'];

        Log::debug('CatalogoService: Inicia guardarMapeo', [
            'empresa_id' => $empresaId,
            'cuentas_recibidas' => $validatedData['cuentas'], // Log the received accounts
        ]);

        // Limpiar el catálogo anterior para esta empresa
        CatalogoCuenta::where('empresa_id', $empresaId)->delete();

        // Crear las nuevas cuentas del catálogo
        foreach ($validatedData['cuentas'] as $cuenta) {
            if (isset($cuenta['cuenta_base_id']) && $cuenta['cuenta_base_id'] !== null) {
                CatalogoCuenta::create([
                    'empresa_id' => $empresaId,
                    'codigo_cuenta' => $cuenta['codigo_cuenta'],
                    'nombre_cuenta' => $cuenta['nombre_cuenta'],
                    'cuenta_base_id' => $cuenta['cuenta_base_id'],
                ]);
                Log::debug('CatalogoService: CatalogoCuenta creada', [
                    'empresa_id' => $empresaId,
                    'codigo_cuenta' => $cuenta['codigo_cuenta'],
                    'cuenta_base_id' => $cuenta['cuenta_base_id'],
                ]);
            } else {
                Log::debug('CatalogoService: Cuenta omitida (cuenta_base_id es nulo)', [
                    'empresa_id' => $empresaId,
                    'codigo_cuenta' => $cuenta['codigo_cuenta'],
                ]);
            }
        }
        Log::debug('CatalogoService: Finaliza guardarMapeo');
    }

    public function procesarCatalogoBasePreview(string $filePath): array
    {
        $import = new class implements WithHeadingRow {};
        $filas = Excel::toArray($import, $filePath)[0] ?? [];

        if (empty($filas)) {
            throw new \Exception("Archivo vacío o no se pudo leer.");
        }

        $accountsData = [];
        $parentCodes = [];
        $codigosUnicos = [];
        $errors = [];
        $warnings = [];

        foreach ($filas as $index => $fila) {
            $filaNorm = $this->normalizeRowKeys($fila);
            $codigo = trim((string)($filaNorm['codigo_cuenta'] ?? $filaNorm['codigo'] ?? ''));
            $nombre = trim((string)($filaNorm['nombre_cuenta'] ?? $filaNorm['nombre'] ?? ''));

            if (empty($codigo) || empty($nombre)) {
                $errors[] = "Fila " . ($index + 2) . ": El código y el nombre son obligatorios.";
                continue;
            }

            if (isset($codigosUnicos[$codigo])) {
                $warnings[] = "Fila " . ($index + 2) . ": Código '{$codigo}' duplicado. Se omitirá.";
                continue;
            }
            $codigosUnicos[$codigo] = true;

            $parentCode = null;
            if (str_contains($codigo, '.')) {
                $parts = explode('.', $codigo);
                array_pop($parts);
                $parentCode = implode('.', $parts);
            } elseif (strlen($codigo) > 1) {
                $parentCode = substr($codigo, 0, -1);
            }

            if ($parentCode) {
                $parentCodes[$parentCode] = true;
            }
            
            $accountsData[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'naturaleza' => $this->determinarNaturalezaCuenta($codigo),
                'parent_code' => $parentCode,
            ];
        }

        $previewData = [];
        foreach ($accountsData as $account) {
            $previewData[] = [
                'codigo' => $account['codigo'],
                'nombre' => $account['nombre'],
                'naturaleza' => $account['naturaleza'],
                'tipo_cuenta' => isset($parentCodes[$account['codigo']]) ? 'AGRUPACION' : 'DETALLE',
            ];
        }

        return ['datos' => $previewData, 'errores' => $errors, 'warnings' => $warnings];
    }

    public function importarCuentasBase(\Illuminate\Http\UploadedFile $file, int $plantillaCatalogoId, int $empresaId): array // <--- Add empresaId to signature
    {
        $collection = Excel::toCollection(new \App\Imports\CuentasBaseImport, $file)->first();
        $warnings = [];

        \Illuminate\Support\Facades\DB::transaction(function () use ($collection, $plantillaCatalogoId, $empresaId, &$warnings) { // <--- Add empresaId to use clause
            $existingAccounts = CuentaBase::where('plantilla_catalogo_id', $plantillaCatalogoId)
                ->get()
                ->keyBy('codigo');
            
            $fileAccountCodes = [];

            // Upsert accounts from file
            foreach ($collection as $row) {
                $filaNorm = $this->normalizeRowKeys($row->toArray()); // Normalize keys for consistent access

                $codigo = trim((string)($filaNorm['codigo_cuenta'] ?? $filaNorm['codigo'] ?? ''));
                $nombre = trim((string)($filaNorm['nombre_cuenta'] ?? $filaNorm['nombre'] ?? ''));

                if (empty($codigo) || empty($nombre)) {
                    // Log::warning("importarCuentasBase: Skipping row due to empty code or name", ['row' => $row->toArray()]);
                    continue;
                }
                $fileAccountCodes[$codigo] = true;

                $naturaleza = 'DEUDORA';
                $firstDigit = substr($codigo, 0, 1);
                if (in_array($firstDigit, ['2', '3', '5'])) {
                    $naturaleza = 'ACREEDORA';
                }

                CuentaBase::updateOrCreate(
                    [
                        'plantilla_catalogo_id' => $plantillaCatalogoId,
                        'codigo' => $codigo,
                    ],
                    [
                        'nombre' => $nombre,
                        'naturaleza' => $naturaleza,
                        'tipo_cuenta' => 'DETALLE', // Default to DETALLE, will be updated later
                    ]
                );
            }

            // Delete old accounts that are not in the new file
            $accountsToDelete = $existingAccounts->filter(function ($account, $codigo) use ($fileAccountCodes) {
                return !isset($fileAccountCodes[$codigo]);
            });

            foreach ($accountsToDelete as $account) {
                $isUsed = \App\Models\DetalleEstado::where('cuenta_base_id', $account->id)->exists();
                if ($isUsed) {
                    $warnings[] = "La cuenta '{$account->codigo} - {$account->nombre}' no se pudo eliminar porque está en uso en un estado financiero.";
                } else {
                    // Also need to check if it's a parent to other accounts
                    $isParent = CuentaBase::where('parent_id', $account->id)->exists();
                    if ($isParent) {
                         $warnings[] = "La cuenta '{$account->codigo} - {$account->nombre}' no se pudo eliminar porque es una cuenta padre. Elimine primero las cuentas hijas.";
                    } else {
                        $account->delete();
                    }
                }
            }

            // Rebuild hierarchy
            $allAccounts = CuentaBase::where('plantilla_catalogo_id', $plantillaCatalogoId)->get();
            $codeIdMap = $allAccounts->pluck('id', 'codigo');
            $parentCodes = [];

            foreach ($allAccounts as $account) {
                $parentCode = null;
                if (str_contains($account->codigo, '.')) {
                    $parts = explode('.', $account->codigo);
                    array_pop($parts);
                    $parentCode = implode('.', $parts);
                } elseif (strlen($account->codigo) > 1) {
                    $parentCode = substr($account->codigo, 0, -1);
                }

                if ($parentCode && isset($codeIdMap[$parentCode])) {
                    $parentCodes[$parentCode] = true;
                    $account->parent_id = $codeIdMap[$parentCode];
                    $account->save();
                }
            }

            // Update account types
            CuentaBase::where('plantilla_catalogo_id', $plantillaCatalogoId)->update(['tipo_cuenta' => 'DETALLE']);
            if (!empty($parentCodes)) {
                CuentaBase::where('plantilla_catalogo_id', $plantillaCatalogoId)
                    ->whereIn('codigo', array_keys($parentCodes))
                    ->update(['tipo_cuenta' => 'AGRUPACION']);
            }

            // --- NEW LOGIC: Upsert CatalogoCuenta records for the Empresa ---
            foreach ($allAccounts as $cuentaBase) {
                CatalogoCuenta::updateOrCreate(
                    [
                        'empresa_id' => $empresaId,
                        'codigo_cuenta' => $cuentaBase->codigo,
                    ],
                    [
                        'cuenta_base_id' => $cuentaBase->id,
                        'nombre_cuenta' => $cuentaBase->nombre,
                    ]
                );
                Log::debug('CatalogoService: CatalogoCuenta upserted desde importarCuentasBase', [
                    'empresa_id' => $empresaId,
                    'cuenta_base_id' => $cuentaBase->id,
                    'codigo_cuenta' => $cuentaBase->codigo,
                ]);
            }
            // --- END NEW LOGIC ---

        });

        return ['warnings' => $warnings];
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

    /**
     * Encuentra la mejor coincidencia para una cuenta de usuario en las cuentas base.
     * @param string $userCodigo
     * @param string $userNombre
     * @param \Illuminate\Support\Collection $cuentasBase
     * @return \App\Models\CuentaBase|null
     */
    private function findBestMatch(string $userCodigo, string $userNombre, $cuentasBase)
    {
        $bestMatch = null;
        $highestScore = 0;

        Log::debug('findBestMatch: Iniciando búsqueda', [
            'userCodigo' => $userCodigo,
            'userNombre' => $userNombre,
            'cuentasBase_count' => $cuentasBase->count(),
        ]);

        foreach ($cuentasBase as $cb) {
            $score = 0;
            // Ponderación de puntajes
            $codigoScore = $this->stringSimilarity($userCodigo, $cb->codigo);
            $nombreScore = $this->stringSimilarity(strtoupper($userNombre), strtoupper($cb->nombre));

            // Exact match de código es un gran plus
            if ($userCodigo === $cb->codigo) {
                $score += 100;
            } else {
                $score += $codigoScore * 0.4; // 40% del puntaje por similitud de código
            }

            $score += $nombreScore * 0.6; // 60% del puntaje por similitud de nombre

            Log::debug('findBestMatch: Comparando', [
                'userCodigo' => $userCodigo,
                'cb_codigo' => $cb->codigo,
                'userNombre' => $userNombre,
                'cb_nombre' => $cb->nombre,
                'codigoScore' => $codigoScore,
                'nombreScore' => $nombreScore,
                'currentScore' => $score,
                'highestScore' => $highestScore,
            ]);

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $cb;
            }
        }

        // Umbral de confianza: si el puntaje no es suficientemente alto, no lo consideramos una coincidencia.
        if ($highestScore > 70) { // Umbral del 70%
            Log::debug('findBestMatch: Coincidencia encontrada', [
                'userCodigo' => $userCodigo,
                'highestScore' => $highestScore,
                'bestMatch_id' => $bestMatch->id ?? null,
                'bestMatch_codigo' => $bestMatch->codigo ?? null,
            ]);
            return $bestMatch;
        }

        Log::debug('findBestMatch: No se encontró coincidencia suficiente', [
            'userCodigo' => $userCodigo,
            'highestScore' => $highestScore,
            'threshold' => 70,
        ]);
        return null;
    }

    /**
     * Calcula la similitud entre dos strings y devuelve un porcentaje.
     * @param string $str1
     * @param string $str2
     * @return float
     */
    private function stringSimilarity(string $str1, string $str2): float
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        if ($len1 === 0 || $len2 === 0) {
            return 0;
        }
        $maxLen = max($len1, $len2);
        $lev = levenshtein($str1, $str2);
        return (($maxLen - $lev) / $maxLen) * 100;
    }
}