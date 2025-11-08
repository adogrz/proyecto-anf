<?php

namespace App\Http\Controllers;

use App\Models\DatoVentaHistorico;
use App\Models\ProyeccionVenta;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Controlador para la gestión CRUD de datos históricos de ventas.
 */
class DatoVentaHistoricoController extends Controller
{
    use AuthorizesRequests;

    /**
     * Nombres completos de los meses en español.
     */
    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /**
     * Obtener el siguiente período lógico para añadir un dato histórico.
     */
    public function getNextPeriod(Request $request, $empresa)
    {
        $this->authorize('create', ProyeccionVenta::class);

        $lastData = DatoVentaHistorico::query()
            ->byEmpresa($empresa)
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->first();

        if (!$lastData) {
            return response()->json([
                'hasData' => false,
                'nextPeriod' => null,
            ]);
        }

        // Calcular el siguiente período
        $nextMes = $lastData->mes + 1;
        $nextAnio = $lastData->anio;

        if ($nextMes > 12) {
            $nextMes = 1;
            $nextAnio++;
        }

        return response()->json([
            'hasData' => true,
            'nextPeriod' => [
                'mes' => $nextMes,
                'anio' => $nextAnio,
            ],
            'lastPeriod' => [
                'mes' => $lastData->mes,
                'anio' => $lastData->anio,
            ],
        ]);
    }

    /**
     * Almacenar un nuevo dato histórico.
     */
    public function store(Request $request, $empresa): RedirectResponse
    {
        $this->authorize('create', ProyeccionVenta::class);

        $validated = $request->validate([
            'anio' => 'required|integer|min:2000|max:2100',
            'mes' => 'required|integer|min:1|max:12',
            'monto' => 'required|numeric|min:0',
        ]);

        // Verificar duplicados
        if ($this->existePeriodo($empresa, $validated['anio'], $validated['mes'])) {
            return back()->withErrors([
                'periodo' => 'Ya existe un registro para este período.',
            ]);
        }

        // Validar que sea el siguiente período en la cadena
        $validacion = $this->validarSiguientePeriodo($empresa, $validated['mes'], $validated['anio']);
        if ($validacion !== true) {
            return back()->withErrors(['periodo' => $validacion]);
        }

        // Crear el registro
        DatoVentaHistorico::create([
            'empresa_id' => $empresa,
            'anio' => $validated['anio'],
            'mes' => $validated['mes'],
            'monto' => $validated['monto'],
        ]);

        return redirect()->route('dashboard.proyecciones', $empresa)
            ->with('success', 'Dato histórico añadido correctamente.');
    }

    /**
     * Actualizar un dato histórico existente.
     * Solo se puede modificar el monto, no el período.
     */
    public function update(Request $request, $empresa, $id): RedirectResponse
    {
        $this->authorize('update', new ProyeccionVenta());

        $datoHistorico = DatoVentaHistorico::query()
            ->byEmpresa($empresa)
            ->findOrFail($id);

        $validated = $request->validate([
            'monto' => 'required|numeric|min:0',
        ]);

        $datoHistorico->update(['monto' => $validated['monto']]);

        return redirect()->route('dashboard.proyecciones', $empresa)
            ->with('success', 'Dato histórico actualizado correctamente.');
    }

    /**
     * Eliminar un dato histórico.
     * Solo se puede eliminar el último dato de la cadena.
     */
    public function destroy(Request $request, $empresa, $id): RedirectResponse
    {
        $this->authorize('delete', new ProyeccionVenta());

        $dato = DatoVentaHistorico::query()
            ->byEmpresa($empresa)
            ->findOrFail($id);

        // Obtener el último dato de la cadena
        $ultimo = DatoVentaHistorico::query()
            ->byEmpresa($empresa)
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->first();

        if (!$ultimo || $ultimo->id !== $dato->id) {
            return back()->withErrors([
                'delete' => 'Solo puedes eliminar el último dato de la cadena.',
            ]);
        }

        $dato->delete();

        return redirect()->route('dashboard.proyecciones', $empresa)
            ->with('success', 'Dato histórico eliminado correctamente.');
    }

    /**
     * Verifica si existe un registro para el período dado.
     */
    private function existePeriodo($empresaId, int $anio, int $mes): bool
    {
        return DatoVentaHistorico::query()
            ->byEmpresa($empresaId)
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->exists();
    }

    /**
     * Valida que el período ingresado sea el siguiente en la cadena.
     *
     * @return true|string True si es válido, mensaje de error si no lo es
     */
    private function validarSiguientePeriodo($empresaId, int $mes, int $anio): bool|string
    {
        $lastData = DatoVentaHistorico::query()
            ->byEmpresa($empresaId)
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->first();

        // Si no hay datos previos, cualquier período es válido
        if (!$lastData) {
            return true;
        }

        $expectedMes = $lastData->mes + 1;
        $expectedAnio = $lastData->anio;

        if ($expectedMes > 12) {
            $expectedMes = 1;
            $expectedAnio++;
        }

        if ($mes !== $expectedMes || $anio !== $expectedAnio) {
            $nombreMes = self::MESES[$expectedMes];
            return "El siguiente período debe ser {$nombreMes} {$expectedAnio}.";
        }

        return true;
    }
}
