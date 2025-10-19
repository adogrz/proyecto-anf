<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\PlantillaCatalogo;
use App\Models\Sector;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmpresasController extends Controller
{
    public function index()
    {
        $empresas = Empresa::with(['sector', 'plantillaCatalogo'])->latest()->get();
        return Inertia::render('Administracion/Empresas/Index', ['empresas' => $empresas]);
    }

    public function create()
    {
        return Inertia::render('Administracion/Empresas/Create', [
            'sectores' => Sector::all(),
            'plantillas' => PlantillaCatalogo::all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'sector_id' => 'required|exists:sectores,id',
            'plantilla_catalogo_id' => 'required|exists:plantillas_catalogo,id',
        ]);

        $empresa = Empresa::create($request->all());

        // Redirect back with the newly created company object flashed to the session
        return redirect()->back()->with('success', 'Empresa creada con éxito.')->with('empresa', $empresa);
    }

    public function show(Empresa $empresa)
    {
        // Eager load relationships
        $empresa->load(['sector', 'plantillaCatalogo', 'catalogoCuentas', 'estadosFinancieros']);
        return Inertia::render('Administracion/Empresas/Show', ['empresa' => $empresa]);
    }

    public function edit(Empresa $empresa)
    {
        return Inertia::render('Administracion/Empresas/Edit', [
            'empresa' => $empresa,
            'sectores' => Sector::all(),
            'plantillas' => PlantillaCatalogo::all(),
        ]);
    }

    public function update(Request $request, Empresa $empresa)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'sector_id' => 'required|exists:sectores,id',
            'plantilla_catalogo_id' => 'required|exists:plantillas_catalogo,id',
        ]);

        $empresa->update($request->all());

        return redirect()->route('empresas.index')->with('success', 'Empresa actualizada con éxito.');
    }

    public function destroy(Empresa $empresa)
    {
        $empresa->delete();
        return redirect()->route('empresas.index')->with('success', 'Empresa eliminada con éxito.');
    }
}