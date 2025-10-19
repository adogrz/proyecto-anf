<?php

namespace App\Http\Controllers;

use App\Imports\EstadoFinancieroImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

use App\Services\EstadoFinancieroService;

class ImportacionController extends Controller
{
    public function previsualizar(Request $request, EstadoFinancieroService $service)
    {
        $request->validate([
            'empresa_id' => ['required', 'exists:empresas,id'],
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv'],
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
}
