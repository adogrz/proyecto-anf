<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\PlantillaCatalogo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Collection;

class PlantillaCatalogoController extends Controller
{
    public function index()
    {
        $plantillas = PlantillaCatalogo::all();
        return Inertia::render('Administracion/PlantillasCatalogo/Index', ['plantillas' => $plantillas]);
    }

    public function create()
    {
        return Inertia::render('Administracion/PlantillasCatalogo/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:plantillas_catalogo',
            'descripcion' => 'nullable|string',
        ]);

        PlantillaCatalogo::create($request->all());

        return redirect()->route('plantillas-catalogo.index')->with('success', 'Plantilla creada con éxito.');
    }

    public function show(PlantillaCatalogo $plantilla_catalogo)
    {
        $cuentas = $plantilla_catalogo->cuentasBase()->orderBy('codigo')->get();
        $tree = $this->buildTree($cuentas);

        return Inertia::render('Administracion/PlantillasCatalogo/Show', [
            'plantilla' => $plantilla_catalogo,
            'cuentas_base_tree' => $tree,
        ]);
    }

    public function edit(PlantillaCatalogo $plantilla_catalogo)
    {
        return Inertia::render('Administracion/PlantillasCatalogo/Edit', ['plantilla' => $plantilla_catalogo]);
    }

    public function update(Request $request, PlantillaCatalogo $plantilla_catalogo)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:plantillas_catalogo,nombre,' . $plantilla_catalogo->id,
            'descripcion' => 'nullable|string',
        ]);

        $plantilla_catalogo->update($request->all());

        return redirect()->route('plantillas-catalogo.index')->with('success', 'Plantilla actualizada con éxito.');
    }

    public function destroy(PlantillaCatalogo $plantilla_catalogo)
    {
        // TODO: Add logic to prevent deletion if in use
        $plantilla_catalogo->delete();
        return redirect()->route('plantillas-catalogo.index')->with('success', 'Plantilla eliminada con éxito.');
    }

    private function buildTree(Collection $elements, $parentId = null): Collection
    {
        $branch = collect();

        foreach ($elements as $element) {
            if ($element->parent_id == $parentId) {
                $children = $this->buildTree($elements, $element->id);
                if ($children->isNotEmpty()) {
                    $element->children = $children;
                }
                $branch->push($element);
            }
        }

        return $branch;
    }
}