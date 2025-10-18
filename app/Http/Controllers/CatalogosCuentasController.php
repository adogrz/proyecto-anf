<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\CatalogoCuenta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Imports\CatalogoImport;
use Maatwebsite\Excel\Facades\Excel;

class CatalogosCuentasController extends Controller
{
    public function showMapeoForm(Empresa $empresa)
    {
        if (!$empresa->plantillaCatalogo) {
            return redirect()->route('empresas.index')->with('error', 'La empresa no tiene una plantilla de catálogo asignada. Asigne una antes de mapear cuentas.');
        }

        $empresa->load('plantillaCatalogo.cuentasBase', 'catalogoCuentas');

        return Inertia::render('Administracion/Empresas/Mapeo', [
            'empresa' => $empresa,
            'cuentasBase' => $empresa->plantillaCatalogo->cuentasBase->where('tipo_cuenta', 'DETALLE'),
            'catalogoEmpresa' => $empresa->catalogoCuentas,
        ]);
    }

    public function importCatalogo(Request $request, Empresa $empresa)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        CatalogoCuenta::where('empresa_id', $empresa->id)->delete();

        Excel::import(new CatalogoImport($empresa->id), $request->file('archivo'));

        return redirect()->route('empresas.mapeo.show', $empresa->id)->with('success', 'Catálogo importado. Listo para mapear.');
    }

    public function updateMapeo(Request $request, Empresa $empresa)
    {
        $request->validate([
            'mapeos' => 'required|array',
            'mapeos.*.id' => 'required|exists:catalogos_cuentas,id',
            'mapeos.*.cuenta_base_id' => 'required|exists:cuentas_base,id',
        ]);

        foreach ($request->mapeos as $mapeo) {
            CatalogoCuenta::where('id', $mapeo['id'])
                          ->where('empresa_id', $empresa->id)
                          ->update(['cuenta_base_id' => $mapeo['cuenta_base_id']]);
        }

        return redirect()->route('empresas.mapeo.show', $empresa->id)->with('success', 'Mapeo de cuentas actualizado con éxito.');
    }

    public function index(Empresa $empresa)
    {
        $catalogosCuentas = $empresa->catalogoCuentas()->with('cuentaBase')->get();

        return Inertia::render('Administracion/Empresas/Catalogos/Index', [
            'empresa' => $empresa,
            'catalogosCuentas' => $catalogosCuentas,
        ]);
    }

    public function create(Empresa $empresa)
    {
        $cuentasBase = $empresa->plantillaCatalogo->cuentasBase()->where('tipo_cuenta', 'DETALLE')->get();

        return Inertia::render('Administracion/Empresas/Catalogos/Create', [
            'empresa' => $empresa,
            'cuentasBase' => $cuentasBase,
        ]);
    }

    public function store(Request $request, Empresa $empresa)
    {
        $request->validate([
            'codigo_cuenta' => 'required|string|max:255',
            'nombre_cuenta' => 'required|string|max:255',
            'cuenta_base_id' => 'nullable|exists:cuentas_base,id',
        ]);

        $empresa->catalogoCuentas()->create([
            'codigo_cuenta' => $request->codigo_cuenta,
            'nombre_cuenta' => $request->nombre_cuenta,
            'cuenta_base_id' => $request->cuenta_base_id,
        ]);

        return redirect()->route('empresas.catalogos.index', $empresa->id)
                         ->with('success', 'Cuenta de catálogo creada con éxito.');
    }

    public function show(CatalogoCuenta $catalogo)
    {
        $catalogo->load('cuentaBase');
        return Inertia::render('Administracion/Empresas/Catalogos/Show', [
            'catalogoCuenta' => $catalogo,
        ]);
    }

    public function edit(CatalogoCuenta $catalogo)
    {
        $empresa = $catalogo->empresa;
        $cuentasBase = $empresa->plantillaCatalogo->cuentasBase()->where('tipo_cuenta', 'DETALLE')->get();

        return Inertia::render('Administracion/Empresas/Catalogos/Edit', [
            'empresa' => $empresa,
            'catalogoCuenta' => $catalogo->load('cuentaBase'),
            'cuentasBase' => $cuentasBase,
        ]);
    }

    public function update(Request $request, CatalogoCuenta $catalogo)
    {
        $request->validate([
            'codigo_cuenta' => 'required|string|max:255',
            'nombre_cuenta' => 'required|string|max:255',
            'cuenta_base_id' => 'nullable|exists:cuentas_base,id',
        ]);

        $catalogo->update([
            'codigo_cuenta' => $request->codigo_cuenta,
            'nombre_cuenta' => $request->nombre_cuenta,
            'cuenta_base_id' => $request->cuenta_base_id,
        ]);

        return redirect()->route('empresas.catalogos.index', $catalogo->empresa_id)
                         ->with('success', 'Cuenta de catálogo actualizada con éxito.');
    }

    public function destroy(CatalogoCuenta $catalogo)
    {
        $empresaId = $catalogo->empresa_id;
        $catalogo->delete();

        return redirect()->route('empresas.catalogos.index', $empresaId)
                         ->with('success', 'Cuenta de catálogo eliminada con éxito.');
    }
}
