<?php

namespace App\Http\Controllers;

use App\Models\DatoVentaHistorico;
use App\Models\ProyeccionVenta;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProyeccionVentasController extends Controller
{
    private function getUserPermissions($user): array
    {
        return [
            'canCreate' => $user?->can('create', ProyeccionVenta::class) ?? false,
            'canEdit' => $user?->can('proyecciones.edit') ?? false,
            'canDelete' => $user?->can('proyecciones.delete') ?? false,
        ];
    }

    use AuthorizesRequests;

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
            // No hay datos históricos, el usuario puede elegir
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
    public function store(Request $request, $empresa)
    {
        $this->authorize('create', ProyeccionVenta::class);

        // Validar los datos
        $validated = $request->validate([
            'anio' => 'required|integer|min:2000|max:2100',
            'mes' => 'required|integer|min:1|max:12',
            'monto' => 'required|numeric|min:0',
        ]);

        // Verificar que no exista un registro con el mismo año y mes
        $exists = DatoVentaHistorico::query()
            ->byEmpresa($empresa)
            ->where('anio', $validated['anio'])
            ->where('mes', $validated['mes'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'periodo' => 'Ya existe un registro para este período.',
            ]);
        }

        // Verificar la regla de la cadena: el nuevo dato debe ser el siguiente período
        $lastData = DatoVentaHistorico::query()
            ->byEmpresa($empresa)
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->first();

        if ($lastData) {
            $expectedMes = $lastData->mes + 1;
            $expectedAnio = $lastData->anio;

            if ($expectedMes > 12) {
                $expectedMes = 1;
                $expectedAnio++;
            }

            if ($validated['mes'] != $expectedMes || $validated['anio'] != $expectedAnio) {
                return back()->withErrors([
                    'periodo' => "El siguiente período debe ser {$this->getMesNombre($expectedMes)} {$expectedAnio}.",
                ]);
            }
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
    public function update(Request $request, $empresa, $id)
    {
        // Autorizar usando instancia (update requiere modelo)
        $this->authorize('update', new ProyeccionVenta());

        // Buscar el dato histórico
        $datoHistorico = DatoVentaHistorico::query()
            ->byEmpresa($empresa)
            ->findOrFail($id);

        // Validar solo el monto
        $validated = $request->validate([
            'monto' => 'required|numeric|min:0',
        ]);

        // Actualizar solo el monto
        $datoHistorico->update([
            'monto' => $validated['monto'],
        ]);

        return redirect()->route('dashboard.proyecciones', $empresa)
            ->with('success', 'Dato histórico actualizado correctamente.');
    }

    /**
     * Eliminar un dato histórico.
     * Solo se puede eliminar el último dato de la cadena.
     */
    public function destroy(Request $request, $empresa, $id)
    {
        // Autorizar acción delete sobre ProyeccionVenta
        $this->authorize('delete', new ProyeccionVenta());

        // Buscar el dato a eliminar (de la empresa)
        $dato = DatoVentaHistorico::query()
            ->byEmpresa($empresa)
            ->findOrFail($id);

        // Obtener el último dato de la cadena
        $ultimo = DatoVentaHistorico::query()
            ->byEmpresa($empresa)
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->first();

        // Validar que el dato seleccionado es el último
        if (!$ultimo || $ultimo->id !== $dato->id) {
            return back()->withErrors([
                'delete' => 'Solo puedes eliminar el último dato de la cadena.',
            ]);
        }

        // Eliminar
        $dato->delete();

        return redirect()->route('dashboard.proyecciones', $empresa)
            ->with('success', 'Dato histórico eliminado correctamente.');
    }

    /**
     * Genera y transmite una plantilla CSV para la carga de datos.
     */
    public function descargarPlantilla(): StreamedResponse
    {
        $this->authorize('create', ProyeccionVenta::class);

        $fileName = 'plantilla_ventas_historicas.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ];

        // Columnas de la plantilla
        $columnas = ['Anio', 'Mes', 'Monto_Venta'];

        $callback = function () use ($columnas) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columnas, ';');
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Obtener el nombre del mes.
     */
    private function getMesNombre($mes): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $meses[$mes] ?? $mes;
    }

    public function dashboard(Request $request, $empresa): Response
    {
        $this->authorize('viewAny', ProyeccionVenta::class);

        $user = $request->user();

        $datosVentaHistorico = DatoVentaHistorico::query()
            ->byEmpresa($empresa)
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        return Inertia::render('ProyeccionVentas/dashboard-proyeccion-ventas', [
            'datosVentaHistorico' => $datosVentaHistorico,
            'permissions' => $this->getUserPermissions($user),
            'empresaId' => $empresa,
        ]);
    }

    public function generar(Request $request, $empresa): Response
    {
        return Inertia::render('ProyeccionVentas/resultados-proyeccion-ventas');
    }
}
