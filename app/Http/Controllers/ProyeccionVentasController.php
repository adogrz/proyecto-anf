<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProyeccionVentasController extends Controller
{
    public function dashboard($empresa)
    {
        return Inertia::render('ProyeccionVentas/dashboard-proyeccion-ventas');
    }

    public function generar(Request $request, $empresa)
    {
        return Inertia::render('ProyeccionVentas/resultados-proyeccion-ventas');
    }
}
