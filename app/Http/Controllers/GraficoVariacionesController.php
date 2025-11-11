<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\CatalogoCuenta;
use App\Models\EstadoFinanciero;
use App\Models\DetalleEstado;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GraficoVariacionesController extends Controller
{
    public function index(Request $request, Empresa $empresa)
    {
        // Obtener todas las cuentas de la empresa para el selector
        $cuentas = CatalogoCuenta::where('empresa_id', $empresa->id)
            ->where('tipo', '!=', 'HEADER')
            ->orderBy('codigo')
            ->select('id', 'codigo', 'nombre')
            ->get();

        $datos = null;

        // Si se seleccionó una cuenta y periodo, obtener los datos
        if ($request->has(['cuenta_id', 'anio_inicio', 'anio_fin'])) {
            $cuentaId = $request->input('cuenta_id');
            $anioInicio = (int) $request->input('anio_inicio');
            $anioFin = (int) $request->input('anio_fin');

            // Validar que el año fin sea mayor o igual al año inicio
            if ($anioFin < $anioInicio) {
                return redirect()->back()->with('error', 'El año final debe ser mayor o igual al año inicial');
            }

            $cuenta = CatalogoCuenta::findOrFail($cuentaId);

            // Obtener los estados financieros en el rango de años
            $estadosFinancieros = EstadoFinanciero::where('empresa_id', $empresa->id)
                ->whereBetween('anio', [$anioInicio, $anioFin])
                ->orderBy('anio')
                ->get();

            // Obtener los valores de la cuenta para cada año
            $valores = [];
            $anioAnterior = null;
            $valorAnterior = null;

            foreach ($estadosFinancieros as $estado) {
                $detalle = DetalleEstado::where('estado_financiero_id', $estado->id)
                    ->where('catalogo_cuenta_id', $cuenta->id)
                    ->first();

                $valorActual = $detalle ? $detalle->valor : 0;

                $dato = [
                    'anio' => $estado->anio,
                    'valor' => $valorActual,
                ];

                // Calcular variaciones si hay año anterior
                if ($anioAnterior !== null && $valorAnterior !== null) {
                    $variacionAbsoluta = $valorActual - $valorAnterior;
                    $variacionPorcentual = $valorAnterior != 0 
                        ? (($variacionAbsoluta / abs($valorAnterior)) * 100) 
                        : 0;

                    $dato['variacion_absoluta'] = $variacionAbsoluta;
                    $dato['variacion_porcentual'] = $variacionPorcentual;
                }

                $valores[] = $dato;
                $anioAnterior = $estado->anio;
                $valorAnterior = $valorActual;
            }

            $datos = [
                'cuenta' => [
                    'id' => $cuenta->id,
                    'codigo' => $cuenta->codigo,
                    'nombre' => $cuenta->nombre,
                ],
                'periodo' => [
                    'inicio' => $anioInicio,
                    'fin' => $anioFin,
                ],
                'datos' => $valores,
            ];
        }

        return Inertia::render('Analisis/GraficoVariaciones', [
            'empresa' => [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre,
            ],
            'cuentas' => $cuentas,
            'datos' => $datos,
        ]);
    }
}
