<?php

namespace App\Http\Controllers;

use App\Services\CalculoRatiosService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class CalculoRatiosController extends Controller
{
    use AuthorizesRequests;
    
    public function __construct(private CalculoRatiosService $ratioService)
    {
    }

    /**
     * Calcula y guarda ratios de una empresa para un año específico
     */
    public function calcular(Request $request, int $empresaId, int $anio)
    {
        $this->authorize('ratios.create');

        try {
            $ratios = $this->ratioService->calcularYGuardar($empresaId, $anio);

            return Inertia::render('Analisis/RatiosGuardados', [
                'empresaId' => $empresaId,
                'anio' => $anio,
                'ratios' => $ratios,
                'mensaje' => 'Ratios calculados y guardados exitosamente.',
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    public function calcularTodos(Request $request, int $empresaId)
{
    $this->authorize('ratios.create');

    try {
        $resultados = $this->ratioService->calcularYGuardarPorEmpresa($empresaId);

        return Inertia::render('Analisis/RatiosGuardados', [
            'empresaId' => $empresaId,
            'resultados' => $resultados,
            'mensaje' => 'Ratios calculados para todos los años disponibles.',
        ]);
    } catch (\Throwable $e) {
        return back()->withErrors(['error' => $e->getMessage()]);
    }
}

}
