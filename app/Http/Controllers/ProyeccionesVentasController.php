<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProyeccionesVentasController extends Controller
{
    public function index(Empresa $empresa)
    {
        $proyecciones = $empresa->proyeccionesVentas()->orderBy('anio')->orderBy('mes')->get()->groupBy(['anio', 'tipo']);

        return Inertia::render('Administracion/Empresas/Proyecciones/Index', [
            'empresa' => $empresa,
            'proyecciones' => $proyecciones,
        ]);
    }

    public function create(Empresa $empresa)
    {
        return Inertia::render('Administracion/Empresas/Proyecciones/Create', [
            'empresa' => $empresa,
        ]);
    }

    public function store(Request $request, Empresa $empresa)
    {
        // TODO: Implement projection logic
        // 1. Validate historical data (12 months)
        // 2. Save historical data
        // 3. Calculate projections (Least Squares, etc.)
        // 4. Save projected data

        return redirect()->route('empresas.proyecciones.index', $empresa->id)->with('success', 'Proyección creada con éxito (lógica pendiente).');
    }
}

