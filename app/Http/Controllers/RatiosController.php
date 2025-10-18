<?php

namespace App\Http\Controllers;

use App\Models\Ratio;
use App\Models\Sector;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RatiosController extends Controller
{
    public function create(Sector $sector)
    {
        return Inertia::render('Administracion/Ratios/Create', ['sector' => $sector]);
    }

    public function store(Request $request, Sector $sector)
    {
        $request->validate([
            'nombre_ratio' => 'required|string|max:255',
            'valor' => 'required|numeric',
            'tipo_ratio' => 'required|string|max:255',
            'mensaje_superior' => 'nullable|string',
            'mensaje_inferior' => 'nullable|string',
            'mensaje_igual' => 'nullable|string',
        ]);

        $sector->ratios()->create($request->all());

        return redirect()->route('sectores.show', $sector)->with('success', 'Ratio creado con éxito.');
    }

    public function edit(Ratio $ratio)
    {
        return Inertia::render('Administracion/Ratios/Edit', ['ratio' => $ratio]);
    }

    public function update(Request $request, Ratio $ratio)
    { 
        $request->validate([
            'nombre_ratio' => 'required|string|max:255',
            'valor' => 'required|numeric',
            'tipo_ratio' => 'required|string|max:255',
            'mensaje_superior' => 'nullable|string',
            'mensaje_inferior' => 'nullable|string',
            'mensaje_igual' => 'nullable|string',
        ]);

        $ratio->update($request->all());

        return redirect()->route('sectores.show', $ratio->sector_id)->with('success', 'Ratio actualizado con éxito.');
    }

    public function destroy(Ratio $ratio)
    {
        $sectorId = $ratio->sector_id;
        $ratio->delete();
        return redirect()->route('sectores.show', $sectorId)->with('success', 'Ratio eliminado con éxito.');
    }
}
