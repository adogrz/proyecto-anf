<?php

namespace App\Http\Controllers;

use App\Models\CuentaBase;
use App\Models\PlantillaCatalogo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class CuentasBaseController extends Controller
{
    public function index()
    {
        $cuentasBase = CuentaBase::with('plantillaCatalogo', 'parent')->get();

        return Inertia::render('Administracion/CuentasBase/Index', [
            'cuentasBase' => $cuentasBase,
            'plantillas' => PlantillaCatalogo::all(),
        ]);
    }

    public function create()
    {
        $plantillas = PlantillaCatalogo::all();
        $cuentasBase = CuentaBase::all();
        return Inertia::render('Administracion/CuentasBase/Create', [
            'plantillas' => $plantillas,
            'cuentasBase' => $cuentasBase,
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

        return Inertia::render('Administracion/CuentasBase/Edit', [
            'cuentaBase' => $cuentaBase,
            'plantillas' => $plantillas,
            'allCuentasBase' => $allCuentasBase,
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
}
