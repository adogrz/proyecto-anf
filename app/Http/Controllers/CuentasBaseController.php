<?php

namespace App\Http\Controllers;

use App\Models\CuentaBase;
use App\Models\PlantillaCatalogo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CuentasBaseController extends Controller
{
    public function index(Request $request)
    {
        $plantillaId = $request->query('plantilla');
        $selectedPlantilla = null;

        $cuentasBaseQuery = CuentaBase::with('plantillaCatalogo', 'parent');

        $breadcrumbs = [
            ['title' => 'Administración', 'href' => '#'],
            ['title' => 'Plantillas de Catálogo', 'href' => route('plantillas-catalogo.index')],
        ];

        if ($plantillaId) {
            $selectedPlantilla = PlantillaCatalogo::findOrFail($plantillaId);
            $cuentasBaseQuery->where('plantilla_catalogo_id', $plantillaId);
            $breadcrumbs[] = ['title' => $selectedPlantilla->nombre, 'href' => route('cuentas-base.index', ['plantilla' => $selectedPlantilla->id])];
            $breadcrumbs[] = ['title' => 'Cuentas Base', 'href' => route('cuentas-base.index', ['plantilla' => $selectedPlantilla->id])];
        } else {
            $cuentasBaseQuery->whereRaw('1 = 0');
            $breadcrumbs[] = ['title' => 'Cuentas Base', 'href' => route('cuentas-base.index')];
        }

        $cuentasBase = $cuentasBaseQuery->get();

        return Inertia::render('Administracion/CuentasBase/Index', [
            'cuentasBase' => $cuentasBase,
            'selectedPlantilla' => $selectedPlantilla,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function create(Request $request)
    {
        $plantillaId = $request->query('plantilla');
        $plantilla = $plantillaId ? PlantillaCatalogo::findOrFail($plantillaId) : null;

        $breadcrumbs = [
            ['title' => 'Administración', 'href' => '#'],
            ['title' => 'Plantillas de Catálogo', 'href' => route('plantillas-catalogo.index')],
        ];

        if ($plantilla) {
            $breadcrumbs[] = ['title' => $plantilla->nombre, 'href' => route('cuentas-base.index', ['plantilla' => $plantilla->id])];
            $breadcrumbs[] = ['title' => 'Cuentas Base', 'href' => route('cuentas-base.index', ['plantilla' => $plantilla->id])];
        }
        
        $breadcrumbs[] = ['title' => 'Crear', 'href' => route('cuentas-base.create', ['plantilla' => $plantillaId])];

        $plantillas = PlantillaCatalogo::all();
        $cuentasBase = CuentaBase::all();
        return Inertia::render('Administracion/CuentasBase/Create', [
            'plantillas' => $plantillas,
            'cuentasBase' => $cuentasBase,
            'plantilla' => $request->query('plantilla'),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'plantilla_catalogo_id' => 'required|exists:plantillas_catalogo,id',
            'codigo' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'tipo_cuenta' => ['required', 'string', Rule::in(['AGRUPACION', 'DETALLE'])],
            'naturaleza' => ['required', 'string', Rule::in(['DEUDORA', 'ACREEDORA'])],
            'parent_id' => 'nullable|exists:cuentas_base,id',
        ]);

        CuentaBase::create($request->all());

        return redirect()->route('cuentas-base.index')
                         ->with('success', 'Cuenta base creada con éxito.');
    }

    public function show(CuentaBase $cuentaBase)
    {
        $plantillas = PlantillaCatalogo::all();
        $allCuentasBase = CuentaBase::all();
        $cuentaBase->load('plantillaCatalogo', 'parent', 'children');

        return Inertia::render('Administracion/CuentasBase/Edit', [
            'cuentaBase' => $cuentaBase,
            'plantillas' => $plantillas,
            'allCuentasBase' => $allCuentasBase,
        ]);
    }

    public function edit(CuentaBase $cuentaBase)
    {
        $plantillas = PlantillaCatalogo::all();
        $allCuentasBase = CuentaBase::all();
        $cuentaBase->load('plantillaCatalogo', 'parent', 'children');

        $breadcrumbs = [
            ['title' => 'Administración', 'href' => '#'],
            ['title' => 'Plantillas de Catálogo', 'href' => route('plantillas-catalogo.index')],
        ];

        if ($cuentaBase->plantillaCatalogo) {
            $breadcrumbs[] = ['title' => $cuentaBase->plantillaCatalogo->nombre, 'href' => route('cuentas-base.index', ['plantilla' => $cuentaBase->plantillaCatalogo->id])];
            $breadcrumbs[] = ['title' => 'Cuentas Base', 'href' => route('cuentas-base.index', ['plantilla' => $cuentaBase->plantillaCatalogo->id])];
        } else {
            $breadcrumbs[] = ['title' => 'Cuentas Base', 'href' => route('cuentas-base.index')];
        }
        
        $breadcrumbs[] = ['title' => $cuentaBase->nombre, 'href' => '#'];
        $breadcrumbs[] = ['title' => 'Editar', 'href' => route('cuentas-base.edit', $cuentaBase->id)];

        return Inertia::render('Administracion/CuentasBase/Edit', [
            'cuentaBase' => $cuentaBase,
            'plantillas' => $plantillas,
            'allCuentasBase' => $allCuentasBase,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function update(Request $request, CuentaBase $cuentaBase)
    {
        $request->validate([
            'plantilla_catalogo_id' => 'required|exists:plantillas_catalogo,id',
            'codigo' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'tipo_cuenta' => ['required', 'string', Rule::in(['AGRUPACION', 'DETALLE'])],
            'naturaleza' => ['required', 'string', Rule::in(['DEUDORA', 'ACREEDORA'])],
            'parent_id' => 'nullable|exists:cuentas_base,id',
        ]);

        $cuentaBase->update($request->all());

        return redirect()->route('cuentas-base.index')
                         ->with('success', 'Cuenta base actualizada con éxito.');
    }

    public function destroy(CuentaBase $cuentaBase)
    {
        $cuentaBase->delete();

        return redirect()->route('cuentas-base.index')
                         ->with('success', 'Cuenta base eliminada con éxito.');
    }

    public function export(Request $request)
    {
        $plantillaId = $request->query('plantilla');

        if (!$plantillaId) {
            return redirect()->route('cuentas-base.index')->with('error', 'Por favor, selecciona una plantilla para exportar.');
        }

        $plantilla = PlantillaCatalogo::findOrFail($plantillaId);
        $cuentas = CuentaBase::with('parent')->where('plantilla_catalogo_id', $plantillaId)->get();
        
        $fileName = 'cuentas_base_' . Str::slug($plantilla->nombre) . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['codigo', 'nombre', 'tipo_cuenta', 'naturaleza', 'parent_codigo'];

        $callback = function() use($cuentas, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($cuentas as $cuenta) {
                $row['codigo']  = $cuenta->codigo;
                $row['nombre']    = $cuenta->nombre;
                $row['tipo_cuenta']  = $cuenta->tipo_cuenta;
                $row['naturaleza']  = $cuenta->naturaleza;
                $row['parent_codigo']  = $cuenta->parent ? $cuenta->parent->codigo : '';

                fputcsv($file, [$row['codigo'], $row['nombre'], $row['tipo_cuenta'], $row['naturaleza'], $row['parent_codigo']]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadTemplate()
    {
        $fileName = 'plantilla_cuentas_base.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['codigo', 'nombre', 'tipo_cuenta', 'naturaleza', 'parent_codigo'];

        $callback = function() use($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
