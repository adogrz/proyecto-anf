<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sector;
use App\Models\RatioSector;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB; // Añadido para transacciones

class RatioAlmanaqueController extends Controller
{
    /**
     * Muestra el formulario para editar los ratios de un sector específico.
     * Usamos Model Binding para cargar el Sector por ID.
     */
    public function edit(Sector $sector)
    {
        // El sector ya está cargado.
        // Ahora cargamos los ratios relacionados usando el ID del sector.
        $ratios = RatioSector::where('sector_id', $sector->id)->get();

        // Se renderiza el componente de React para la edición.
        return Inertia::render('Administracion/Sectores/RatiosAlmanaque/RatiosSectorForm', [
            // Renombramos la prop 'ratios' a 'ratiosIniciales' para que coincida con el componente de React.
            'sector' => $sector,
            'ratiosIniciales' => $ratios, 
        ]);
    }

    /**
     * Procesa y guarda el array de ratios (crea nuevos o actualiza existentes).
     */
    public function guardar(Request $request, Sector $sector)
    {
        // Usamos Model Route Binding para inyectar el Sector. 
        // Ya no necesitamos el parámetro $id, usamos $sector->id.
        // Esto previene errores si el ID del sector no existe.
        
        $request->validate([
            'ratios' => 'nullable|array',
            'ratios.*.nombre_ratio' => 'required|string|max:255',
            'ratios.*.valor_referencia' => 'nullable|numeric',
            'ratios.*.anio' => 'nullable|string|max:4',
            'ratios.*.fuente' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();
            
            // 1. Recorrer y guardar/actualizar
            foreach ($request->ratios as $ratio) {
                RatioSector::updateOrCreate(
                    // La clave de búsqueda: si tiene ID, actualiza; si es null, crea.
                    ['id' => $ratio['id'] ?? null], 
                    [
                        'sector_id' => $sector->id, // Usamos el ID del sector inyectado
                        'nombre_ratio' => $ratio['nombre_ratio'],
                        'valor_referencia' => $ratio['valor_referencia'],
                        'anio' => $ratio['anio'],
                        'fuente' => $ratio['fuente'],
                    ]
                );
            }
            
            // 2. Manejo de eliminación (Opcional, pero recomendado)
            // Si quieres eliminar ratios que estaban antes y ya no están en el array del form.
            // Para la primera versión, mantendremos este paso simple, asumiendo que solo se actualiza/crea.
            
            DB::commit();

            return redirect()->back()->with('success', 'Ratios guardados correctamente.');

        } catch (\Exception $e) {
            dd($e->getMessage());
            DB::rollBack();
            // Esto es importante para ver el error real
            \Log::error("Error al guardar ratios: " . $e->getMessage()); 
            
            // En caso de error, volvemos atrás con un mensaje de error
            return redirect()->back()->with('error', 'Hubo un error al guardar los ratios.');
        }
    }
}