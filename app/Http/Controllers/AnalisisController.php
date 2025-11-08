<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\FinancialService;

class AnalisisController extends Controller
{
    public function obtenerComparacionRatios(Empresa $empresa, int $anio)
    {
        // Renderiza la vista de ratios comparativos (sin cálculos aún)
        // El cálculo se hará por AJAX desde el frontend
        return Inertia::render('Analisis/Ratios', [
            'empresa' => $empresa,
            'anio' => $anio,
        ]);
    }

    public function calcularRatios(Request $request)
{
    $service = new \App\Services\FinancialRatioService();

    $anioA = $request->input('anioA');
    $anioB = $request->input('anioB');

    if (!$anioA || !$anioB) {
        return response()->json(['error' => 'Datos incompletos'], 400);
    }

    $ratiosA = $service->calcularRatios($anioA);
    $ratiosB = $service->calcularRatios($anioB);
    $comparacion = $service->compararRatios($ratiosA, $ratiosB);

    return response()->json([
        'anioA' => $ratiosA,
        'anioB' => $ratiosB,
        'comparacion' => $comparacion,
    ]);
}


    public function obtenerAnalisisHorizontal(Empresa $empresa)
    {
        // TODO: Implement horizontal analysis logic
        return Inertia::render('Analisis/Horizontal', [
            'empresa' => $empresa,
            // 'analysisData' => $analysisData, // Placeholder
        ]);
    }

    public function obtenerHistorialCuenta(Request $request, Empresa $empresa)
    {
        $request->validate([
            'cuenta_id' => 'required|exists:cuentas_base,id',
        ]);
        // TODO: Implement account history logic
        return Inertia::render('Analisis/HistorialCuenta', [
            'empresa' => $empresa,
            'cuentaId' => $request->input('cuenta_id'),
            // 'historyData' => $historyData, // Placeholder
        ]);
    }
}
