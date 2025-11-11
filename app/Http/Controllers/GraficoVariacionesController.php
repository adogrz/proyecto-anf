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
            ->orderBy('codigo_cuenta')
            ->select('id', 'codigo_cuenta as codigo', 'nombre_cuenta as nombre')
            ->get();

        // Obtener los años disponibles de los estados financieros de la empresa
        $aniosDisponibles = EstadoFinanciero::where('empresa_id', $empresa->id)
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->unique()
            ->values()
            ->toArray();

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

            // Obtener los estados financieros en el rango de años (agrupados por año)
            $estadosFinancieros = EstadoFinanciero::where('empresa_id', $empresa->id)
                ->whereBetween('anio', [$anioInicio, $anioFin])
                ->orderBy('anio')
                ->get()
                ->groupBy('anio'); // Agrupar por año para evitar duplicados

            // Obtener los valores de la cuenta para cada año
            $valores = [];
            $anioAnterior = null;
            $valorAnterior = null;

            foreach ($estadosFinancieros as $anio => $estados) {
                // Sumar los valores de todos los estados financieros de ese año para esta cuenta
                $valorTotal = 0;
                foreach ($estados as $estado) {
                    $detalle = DetalleEstado::where('estado_financiero_id', $estado->id)
                        ->where('catalogo_cuenta_id', $cuenta->id)
                        ->first();
                    
                    if ($detalle) {
                        $valorTotal += $detalle->valor;
                    }
                }

                $dato = [
                    'anio' => (int) $anio,
                    'valor' => $valorTotal,
                ];

                // Calcular variaciones si hay año anterior
                if ($anioAnterior !== null && $valorAnterior !== null) {
                    $variacionAbsoluta = $valorTotal - $valorAnterior;
                    $variacionPorcentual = $valorAnterior != 0 
                        ? (($variacionAbsoluta / abs($valorAnterior)) * 100) 
                        : 0;

                    $dato['variacion_absoluta'] = $variacionAbsoluta;
                    $dato['variacion_porcentual'] = $variacionPorcentual;
                }

                $valores[] = $dato;
                $anioAnterior = (int) $anio;
                $valorAnterior = $valorTotal;
            }

            $datos = [
                'cuenta' => [
                    'id' => $cuenta->id,
                    'codigo' => $cuenta->codigo_cuenta,
                    'nombre' => $cuenta->nombre_cuenta,
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
            'aniosDisponibles' => $aniosDisponibles,
            'datos' => $datos,
        ]);
    }
}
