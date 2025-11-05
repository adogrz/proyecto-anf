<?php

namespace App\Services;

use App\Models\CatalogoCuenta;
use App\Models\CuentaBase;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class CatalogoService
{
    public function procesarAutomap(string $filePath, int $plantillaId): array
    {
        HeadingRowFormatter::default('custom');
        $errores = [];

        try {
            $import = new class implements WithHeadingRow {};
            $filas = Excel::toArray($import, $filePath)[0];
        } catch (\Exception $e) {
            $errores[] = 'El archivo no es un formato de Excel/CSV válido o está corrupto.';
            return ['datos' => [], 'errores' => $errores];
        }

        $cuentasBase = CuentaBase::where('plantilla_catalogo_id', $plantillaId)
            ->where('tipo_cuenta', 'DETALLE')
            ->get()
            ->keyBy(fn($item) => strtolower(trim($item->nombre)));

        $mapeoResultado = [];

        if (empty($filas)) {
            $errores[] = 'El archivo está vacío o no contiene filas de datos.';
            return ['datos' => [], 'errores' => $errores];
        }

        // Verificar cabeceras
        $cabeceras = array_keys($filas[0]);
        if (!in_array('codigo_cuenta', $cabeceras) || !in_array('nombre_cuenta', $cabeceras)) {
            $errores[] = "El archivo debe contener las columnas 'codigo_cuenta' y 'nombre_cuenta'.";
            return ['datos' => [], 'errores' => $errores];
        }

        foreach ($filas as $index => $fila) {
            $numeroFila = $index + 2;

            if (empty($fila['codigo_cuenta'])) {
                $errores[] = "Fila {$numeroFila}: La columna 'codigo_cuenta' no puede estar vacía.";
                continue;
            }
            if (empty($fila['nombre_cuenta'])) {
                $errores[] = "Fila {$numeroFila}: La columna 'nombre_cuenta' no puede estar vacía.";
                continue;
            }

            $codigo = $fila['codigo_cuenta'];
            $nombre = $fila['nombre_cuenta'];
            $nombreNormalizado = strtolower(trim($nombre));

            $cuentaMapeada = $cuentasBase->get($nombreNormalizado);

            $mapeoResultado[] = [
                'codigo_cuenta' => $codigo,
                'nombre_cuenta' => $nombre,
                'cuenta_base_id' => $cuentaMapeada ? $cuentaMapeada->id : null,
                'cuenta_base_nombre' => $cuentaMapeada ? $cuentaMapeada->nombre : null,
            ];
        }

        HeadingRowFormatter::default('slug');
        return ['datos' => $mapeoResultado, 'errores' => $errores];
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
}