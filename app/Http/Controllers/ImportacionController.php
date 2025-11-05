<?php

namespace App\Http\Controllers;

use App\Imports\EstadoFinancieroImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

use App\Services\CatalogoService;
use App\Services\EstadoFinancieroService;

class ImportacionController extends Controller
{
    public function previsualizar(Request $request, EstadoFinancieroService $service)
    {
        $request->validate([
            'empresa_id' => ['required', 'exists:empresas,id'],
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ], [
            'archivo.mimes' => 'El archivo debe ser de tipo Excel (.xlsx, .xls) o CSV (.csv).',
        ]);

        $resultado = $service->previsualizar(
            $request->input('empresa_id'),
            $request->file('archivo')->getRealPath()
        );

        if (!empty($resultado['errores'])) {
            return response()->json(['errors' => $resultado['errores']], 422);
        }

        return response()->json($resultado['datos']);
    }

    public function guardarEstadoFinanciero(Request $request, EstadoFinancieroService $service)
    {
        $validated = $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'anio' => 'required|numeric',
            'tipo_estado' => 'required|string',
            'detalles' => 'required|array',
            'detalles.*.catalogo_cuenta_id' => 'required|exists:catalogos_cuentas,id',
            'detalles.*.valor' => 'required|numeric',
        ]);

        $service->guardarDesdePrevisualizacion($validated);

        return redirect()->route('empresas.index')->with('success', 'Estado financiero importado con éxito.');
    }
    public function wizard()
    {
        $sectores = \App\Models\Sector::orderBy('nombre')->get();
        $plantillas = \App\Models\PlantillaCatalogo::with('cuentasBase')->orderBy('nombre')->get();
        $empresas = \App\Models\Empresa::orderBy('nombre')->get();

        return \Inertia\Inertia::render('Importacion/Wizard', [
            'sectores' => $sectores,
            'plantillas' => $plantillas,
            'empresas' => $empresas,
        ]);
    }

    public function automap(Request $request, CatalogoService $service)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'plantilla_catalogo_id' => ['required', 'exists:plantillas_catalogo,id'],
        ], [
            'archivo.mimes' => 'El archivo debe ser de tipo Excel (.xlsx, .xls) o CSV (.csv).',
        ]);

        return response()->json($resultado);
    }

    public function guardarMapeo(Request $request, CatalogoService $service)
    {
        $validated = $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'cuentas' => 'required|array',
            'cuentas.*.codigo_cuenta' => 'required|string',
            'cuentas.*.nombre_cuenta' => 'required|string',
            'cuentas.*.cuenta_base_id' => 'nullable|exists:cuentas_base,id',
        ]);

        $service->guardarMapeo($validated);

        return response()->json(['message' => 'Mapeo guardado con éxito.']);
    }
}
