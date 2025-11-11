<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Imports\CuentasBaseImport;
use App\Services\CatalogoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PlantillaCatalogo;

class ImportacionBaseController extends Controller
{
    public function __construct(private CatalogoService $catalogoService)
    {
    }

    public function index()
    {
        return Inertia::render('Administracion/ImportacionBase/Index', [
            'plantillas' => PlantillaCatalogo::all(),
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xls,xlsx'],
        ]);

        $collections = Excel::toCollection(new CuentasBaseImport, $request->file('file'));

        // Return the first sheet's data, limited to 100 rows for preview
        return response()->json($collections->first()->take(100));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xls,xlsx'],
            'plantilla_catalogo_id' => ['required', 'exists:plantillas_catalogo,id'],
        ]);

        try {
            $this->catalogoService->importarCuentasBase(
                $request->file('file'),
                $request->input('plantilla_catalogo_id')
            );
        } catch (\Exception $e) {
            return back()->withErrors(['general' => 'Error durante la importación: ' . $e->getMessage()]);
        }

        return redirect()->route('admin.importacion-base.index')->with('success', 'Catálogo importado con éxito.');
    }
}
