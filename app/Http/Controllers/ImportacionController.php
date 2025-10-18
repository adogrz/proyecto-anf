<?php

namespace App\Http\Controllers;

use App\Imports\EstadoFinancieroImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

use Maatwebsite\Excel\Validators\ValidationException;

class ImportacionController extends Controller
{
    public function create()
    {
        $empresas = \App\Models\Empresa::orderBy('nombre')->get();

        return \Inertia\Inertia::render('Importacion/Create', [
            'empresas' => $empresas,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'empresa_id' => ['required', 'exists:empresas,id'],
            'anio' => ['required', 'numeric', 'min:1900'],
            'tipo_estado' => ['required', 'string', 'in:balance_general,estado_resultados'],
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            $import = new EstadoFinancieroImport(
                $request->input('empresa_id'),
                $request->input('anio'),
                $request->input('tipo_estado')
            );
            
            Excel::import($import, $request->file('archivo'));

            return redirect()->back()->with('success', '¡Estado financiero importado con éxito!');
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = 'Fila ' . $failure->row() . ': ' . implode(', ', $failure->errors());
            }
            return redirect()->back()->with('error', 'Ocurrieron errores de validación.')->with('validation_errors', $errorMessages);
        } catch (\Exception $e) {
            // Log the exception message for debugging
            logger()->error($e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error durante la importación. Por favor, verifique el formato del archivo.');
        }
    }
}
