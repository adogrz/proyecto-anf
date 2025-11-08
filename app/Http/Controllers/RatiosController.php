<?php

namespace App\Http\Controllers;

use App\Models\Ratio;
use App\Models\Sector;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\FinancialRatioService;

class RatiosController extends Controller
{
    // ============================================================
    //  CRUD EXISTENTE (lo dejo tal cual; completa tus implementaciones)
    // ============================================================

    /** Mostrar formulario de creación de un ratio (Administración) */
    public function create(Sector $sector)
    {
        return Inertia::render('Administracion/Ratios/Create', [
            'sector' => $sector,
        ]);
    }

    /** Guardar un ratio (Administración) */
    public function store(Request $request, Sector $sector)
    {
        // TODO: tu lógica de guardado
        // $validated = $request->validate([...]);
        // Ratio::create([...]);
        return back()->with('success', 'Ratio creado.');
    }

    /** Editar un ratio (Administración) */
    public function edit(Ratio $ratio)
    {
        return Inertia::render('Administracion/Ratios/Edit', [
            'ratio' => $ratio,
        ]);
    }

    /** Actualizar un ratio (Administración) */
    public function update(Request $request, Ratio $ratio)
    {
        // TODO: tu lógica de actualización
        // $validated = $request->validate([...]);
        // $ratio->update([...]);
        return back()->with('success', 'Ratio actualizado.');
    }

    /** Eliminar un ratio (Administración) */
    public function destroy(Ratio $ratio)
    {
        // TODO: tu lógica de borrado
        // $ratio->delete();
        return back()->with('success', 'Ratio eliminado.');
    }

    
    public function showCalculoForm()
    {
        return Inertia::render('Analisis/Ratios', [
            'resultados' => null,
            'oldData'    => null,
            'errors'     => (object)[],
        ]);
    }

    /**
     * Procesa el formulario y retorna los ratios comparativos para 2 años.
     * Acepta entradas agrupadas por año (y1, y2) para mantener el front ordenado.
     *
     * Campos esperados (todos opcionales y numéricos):
     *   y1/y2:
     *     AC, PC, Inv, AT, PT, PAT, VN, COGS, UN  (nombres cortos estándar)
     * Etiquetas opcionales: labels.y1, labels.y2
     */
    public function calculateRatios(Request $request, FinancialRatioService $svc)
    {
        $data = $request->validate([
            // Año 1
            'y1.AC'   => 'nullable|numeric',
            'y1.PC'   => 'nullable|numeric',
            'y1.Inv'  => 'nullable|numeric',
            'y1.AT'   => 'nullable|numeric',
            'y1.PT'   => 'nullable|numeric',
            'y1.PAT'  => 'nullable|numeric',
            'y1.VN'   => 'nullable|numeric',
            'y1.COGS' => 'nullable|numeric',
            'y1.UN'   => 'nullable|numeric',
            // Año 2
            'y2.AC'   => 'nullable|numeric',
            'y2.PC'   => 'nullable|numeric',
            'y2.Inv'  => 'nullable|numeric',
            'y2.AT'   => 'nullable|numeric',
            'y2.PT'   => 'nullable|numeric',
            'y2.PAT'  => 'nullable|numeric',
            'y2.VN'   => 'nullable|numeric',
            'y2.COGS' => 'nullable|numeric',
            'y2.UN'   => 'nullable|numeric',
            // Etiquetas opcionales
            'labels.y1' => 'nullable|string',
            'labels.y2' => 'nullable|string',
        ]);

        // Calcula todos los ratios y empaqueta comparación (Δ Abs y Δ % los hará el front)
        $resultados = $svc->compute($data['y1'] ?? [], $data['y2'] ?? []);

        // Renderiza la misma página con resultados y los datos que el usuario envió
        return Inertia::render('Analisis/Ratios', [
            'resultados' => $resultados,
            'oldData'    => $data,
            'errors'     => (object)[],
        ]);
    }
}
