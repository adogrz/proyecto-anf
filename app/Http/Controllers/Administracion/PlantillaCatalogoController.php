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
        $breadcrumbs = [
            ['title' => 'Administración', 'href' => '#'],
            ['title' => 'Plantillas de Catálogo', 'href' => '#'],
        ];
        return Inertia::render('Administracion/PlantillasCatalogo/Index', [
            'plantillas' => $plantillas,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function create()
    {
        $breadcrumbs = [
            ['title' => 'Administración', 'href' => '#'],
            ['title' => 'Plantillas de Catálogo', 'href' => route('plantillas-catalogo.index')],
            ['title' => 'Crear', 'href' => '#'],
        ];
        return Inertia::render('Administracion/PlantillasCatalogo/Create', [
            'breadcrumbs' => $breadcrumbs,
        ]);
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
        $plantilla_catalogo->loadCount(['cuentasBase', 'empresas']); // Load counts directly
        $cuentas = $plantilla_catalogo->cuentasBase()->orderBy('codigo')->get();
        $tree = $this->buildTree($cuentas);

        $breadcrumbs = [
            ['title' => 'Administración', 'href' => '#'],
            ['title' => 'Plantillas de Catálogo', 'href' => route('plantillas-catalogo.index')],
            ['title' => $plantilla_catalogo->nombre, 'href' => '#'],
        ];

        return Inertia::render('Administracion/PlantillasCatalogo/Show', [
            'plantilla' => $plantilla_catalogo,
            'cuentas_base_tree' => $tree,
            'empresas_count' => $plantilla_catalogo->empresas_count, // Use the loaded count
            'cuentas_base_count' => $plantilla_catalogo->cuentas_base_count, // Pass cuentas_base_count
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function edit(PlantillaCatalogo $plantilla_catalogo)
    {
        $breadcrumbs = [
            ['title' => 'Administración', 'href' => '#'],
            ['title' => 'Plantillas de Catálogo', 'href' => route('plantillas-catalogo.index')],
            ['title' => $plantilla_catalogo->nombre, 'href' => '#'],
            ['title' => 'Editar', 'href' => '#'],
        ];
        return Inertia::render('Administracion/PlantillasCatalogo/Edit', [
            'plantilla' => $plantilla_catalogo,
            'breadcrumbs' => $breadcrumbs,
        ]);
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
        if ($plantilla_catalogo->empresas()->exists()) {
            return redirect()->route('plantillas-catalogo.index')->with('error', 'No se puede eliminar la plantilla porque está en uso por una o más empresas.');
        }

        // Add check for associated CuentaBase records
        if ($plantilla_catalogo->cuentasBase()->exists()) {
            return redirect()->route('plantillas-catalogo.index')->with('error', 'No se puede eliminar la plantilla porque tiene cuentas base asociadas. Elimine las cuentas base primero.');
        }

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