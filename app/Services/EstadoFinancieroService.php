<?php

namespace App\Services;

use App\Models\CatalogoCuenta;
use App\Models\EstadoFinanciero;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class EstadoFinancieroService
{
    public function previsualizar(int $empresaId, string $filePath): array
    {
        HeadingRowFormatter::default('custom'); // Evita la conversión a snake_case

        $catalogoMapeado = CatalogoCuenta::where('empresa_id', $empresaId)
            ->whereNotNull('cuenta_base_id')
            ->with('cuentaBase')
            ->get()
            ->keyBy('codigo_cuenta');

        $import = new class implements WithHeadingRow {};
        $filas = Excel::toArray($import, $filePath)[0];
        
        $datosPrevisualizacion = [];
        $errores = [];
        $codigosProcesados = [];

        foreach ($filas as $index => $fila) {
            $numeroFila = $index + 2; // +2 para la cabecera y el índice base 1
            $codigoCuenta = $fila['codigo_cuenta'] ?? null;
            $valor = $fila['valor'] ?? null;

            if (!$codigoCuenta) {
                $errores[] = "Error en la fila {$numeroFila}: La columna 'codigo_cuenta' está vacía.";
                continue;
            }
            if (!is_numeric($valor)) {
                $errores[] = "Error en la fila {$numeroFila} (Cuenta: {$codigoCuenta}): El valor '{$valor}' no es numérico.";
                continue;
            }
            if (!$catalogoMapeado->has($codigoCuenta)) {
                $errores[] = "Error en la fila {$numeroFila}: La cuenta '{$codigoCuenta}' no existe en su catálogo o no ha sido mapeada.";
                continue;
            }
            if (in_array($codigoCuenta, $codigosProcesados)) {
                $errores[] = "Error en la fila {$numeroFila}: La cuenta '{$codigoCuenta}' está duplicada.";
                continue;
            }

            $codigosProcesados[] = $codigoCuenta;
            $cuentaCatalogo = $catalogoMapeado[$codigoCuenta];

            $datosPrevisualizacion[] = [
                'catalogo_cuenta_id' => $cuentaCatalogo->id,
                'nombre_cuenta' => $cuentaCatalogo->nombre_cuenta,
                'valor' => $valor,
                'cuenta_base_nombre' => $cuentaCatalogo->cuentaBase->nombre,
            ];
        }

        HeadingRowFormatter::default('slug'); // Revertir al default
        return ['datos' => $datosPrevisualizacion, 'errores' => $errores];
    }

    public function guardarDesdePrevisualizacion(array $validatedData): void
    {
        DB::transaction(function () use ($validatedData) {
            $estadoFinanciero = EstadoFinanciero::updateOrCreate(
                [
                    'empresa_id' => $validatedData['empresa_id'],
                    'anio' => $validatedData['anio'],
                    'tipo_estado' => $validatedData['tipo_estado'],
                ],
                [] // No hay campos que actualizar, solo buscar o crear
            );

            $estadoFinanciero->detalles()->delete();

            $estadoFinanciero->detalles()->createMany($validatedData['detalles']);
        });
    }
}