<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalisisController extends Controller
{
    public function obtenerComparacionRatios(Empresa $empresa, int $anio)
    {
        // TODO: Implement ratio comparison logic
        return Inertia::render('Analisis/Ratios', [
            'empresa' => $empresa,
            'anio' => $anio,
            // 'ratiosData' => $ratiosData, // Placeholder
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
