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
        HeadingRowFormatter::default('custom'); // Evita la conversión a snake_case

        $cuentasBase = CuentaBase::where('plantilla_catalogo_id', $plantillaId)
            ->where('tipo_cuenta', 'DETALLE')
            ->get()
            ->keyBy(fn($item) => strtolower($item->nombre));

        $import = new class implements WithHeadingRow {};
        $filas = Excel::toArray($import, $filePath)[0];

        $mapeoResultado = collect($filas)->map(function ($fila) use ($cuentasBase) {
            $codigo = $fila['codigo_cuenta'] ?? 'N/A';
            $nombre = $fila['nombre_cuenta'] ?? 'N/A';
            $nombreNormalizado = strtolower(trim($nombre));

            $cuentaMapeada = null;

            if ($cuentasBase->has($nombreNormalizado)) {
                $cuentaMapeada = $cuentasBase[$nombreNormalizado];
            }

            return [
                'codigo_cuenta' => $codigo,
                'nombre_cuenta' => $nombre,
                'cuenta_base_id' => $cuentaMapeada ? $cuentaMapeada->id : null,
                'cuenta_base_nombre' => $cuentaMapeada ? $cuentaMapeada->nombre : null,
            ];
        });

        HeadingRowFormatter::default('slug'); // Revertir al default
        return $mapeoResultado->all();
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