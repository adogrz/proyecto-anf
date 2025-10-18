<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SectoresController extends Controller
{
    public function index()
    {
        $sectores = Sector::latest()->get();
        return Inertia::render('Administracion/Sectores/Index', ['sectores' => $sectores]);
    }

    public function create()
    {
        return Inertia::render('Administracion/Sectores/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:sectores',
            'descripcion' => 'nullable|string',
        ]);

        Sector::create($request->all());

        return redirect()->route('sectores.index')->with('success', 'Sector creado con éxito.');
    }

    public function show(Sector $sector)
    {
        // Since ratios are a nested resource, we can show them here.
        $sector->load('ratios');
        return Inertia::render('Administracion/Sectores/Show', [
            'sector' => $sector,
        ]);
    }

    public function edit(Sector $sector)
    {
        return Inertia::render('Administracion/Sectores/Edit', ['sector' => $sector]);
    }

    public function update(Request $request, Sector $sector)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:sectores,nombre,' . $sector->id,
            'descripcion' => 'nullable|string',
        ]);

        $sector->update($request->all());

        return redirect()->route('sectores.index')->with('success', 'Sector actualizado con éxito.');
    }

    public function destroy(Sector $sector)
    {
        $sector->delete();
        return redirect()->route('sectores.index')->with('success', 'Sector eliminado con éxito.');
    }
}
