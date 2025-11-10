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
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'sector_id' => 'required|exists:sectores,id',
            'plantilla_catalogo_id' => 'required_without:nombre_plantilla|nullable|exists:plantillas_catalogo,id',
            'nombre_plantilla' => 'required_without:plantilla_catalogo_id|nullable|string|max:255|unique:plantillas_catalogo,nombre',
        ]);

        $plantillaId = null; // Initialize to null

        // Si se provee un nombre de plantilla, crearla y usar su ID
        if (!empty($validated['nombre_plantilla'])) {
            $nuevaPlantilla = PlantillaCatalogo::create([
                'nombre' => $validated['nombre_plantilla'],
            ]);
            $plantillaId = $nuevaPlantilla->id;
        } else {
            // Si no se provee nombre_plantilla, entonces plantilla_catalogo_id debe estar presente (por las reglas de validación)
            $plantillaId = $validated['plantilla_catalogo_id'];
        }

        $empresa = Empresa::create([
            'nombre' => $validated['nombre'],
            'sector_id' => $validated['sector_id'],
            'plantilla_catalogo_id' => $plantillaId,
        ]);

        // Cargar la relación para que esté disponible en el objeto de respuesta
        $empresa->load('plantillaCatalogo');

        // Redirigir con el objeto empresa recién creado
        return redirect()->back()
            ->with('success', 'Empresa creada con éxito.')
            ->with('empresa', $empresa);
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

    public function checkCatalogStatus(Empresa $empresa)
    {
        // Load the plantillaCatalogo relationship
        $empresa->load('plantillaCatalogo');

        $hasCuentasBase = false;
        if ($empresa->plantillaCatalogo) {
            $hasCuentasBase = $empresa->plantillaCatalogo->cuentasBase()->exists();
        }

        return response()->json([
            'has_catalog' => $hasCuentasBase,
        ]);
    }
}