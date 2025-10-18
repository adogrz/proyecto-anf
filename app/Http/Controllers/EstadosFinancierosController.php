<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\EstadoFinanciero;
use App\Models\Empresa;
use Inertia\Inertia;

class EstadosFinancierosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Empresa $empresa)
    {
        $empresa->load('estadosFinancieros');

        return Inertia::render('Administracion/Empresas/EstadosFinancieros/Index', [
            'empresa' => $empresa,
            'estadosFinancieros' => $empresa->estadosFinancieros,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(EstadoFinanciero $estadoFinanciero)
    {
        $estadoFinanciero->load(['empresa', 'detalles.cuenta.cuentaBase']);

        // Agrupar detalles por tipo de cuenta base (Activo, Pasivo, etc.)
        $detallesAgrupados = $estadoFinanciero->detalles->groupBy(function ($detalle) {
            // Navegar a través de las relaciones para encontrar el ancestro raíz
            $cuentaBase = $detalle->cuenta->cuentaBase;
            while ($cuentaBase && $cuentaBase->parent_id) {
                $cuentaBase = $cuentaBase->parent;
            }
            return $cuentaBase ? $cuentaBase->nombre : 'Sin categoría';
        });

        return Inertia::render('Administracion/Empresas/EstadosFinancieros/Show', [
            'estadoFinanciero' => $estadoFinanciero,
            'detallesAgrupados' => $detallesAgrupados,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
