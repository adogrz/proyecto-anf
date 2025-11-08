<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\CatalogoCuenta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Imports\CatalogoImport;
use App\Services\CatalogoService;

class CatalogosCuentasController extends Controller
{
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

        $empresa->catalogoCuentas()->create($request->all());

        return redirect()->route('empresas.catalogos.index', $empresa->id)->with('success', 'Cuenta creada.');
    }

    public function show(CatalogoCuenta $catalogo)
    {
        return Inertia::render('Administracion/Empresas/Catalogos/Show', [
            'catalogoCuenta' => $catalogo->load('cuentaBase'),
        ]);
    }

    public function edit(CatalogoCuenta $catalogo)
    {
        $empresa = $catalogo->empresa;
        $cuentasBase = $empresa->plantillaCatalogo->cuentasBase()->where('tipo_cuenta', 'DETALLE')->get();
        return Inertia::render('Administracion/Empresas/Catalogos/Edit', [
            'catalogoCuenta' => $catalogo,
            'cuentasBase' => $cuentasBase,
            'empresa' => $empresa,
        ]);
    }

    public function update(Request $request, CatalogoCuenta $catalogo)
    {
        $request->validate([
            'codigo_cuenta' => 'required|string|max:255',
            'nombre_cuenta' => 'required|string|max:255',
            'cuenta_base_id' => 'nullable|exists:cuentas_base,id',
        ]);

        $catalogo->update($request->all());

        return redirect()->route('empresas.catalogos.index', $catalogo->empresa_id)->with('success', 'Cuenta actualizada.');
    }

    public function destroy(CatalogoCuenta $catalogo)
    {
        $empresaId = $catalogo->empresa_id;
        $catalogo->delete();

        return redirect()->route('empresas.catalogos.index', $empresaId)
                         ->with('success', 'Cuenta de catálogo eliminada con éxito.');
    }
}
