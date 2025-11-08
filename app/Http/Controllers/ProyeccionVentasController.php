<?php

namespace App\Http\Controllers;

use App\Models\DatoVentaHistorico;
use App\Models\ProyeccionVenta;
use App\Services\FormateoProyeccionService;
use App\Services\ImportacionVentasService;
use App\Services\ProyeccionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador para la gestión de proyecciones de ventas.
 * Responsable de: visualización, generación de proyecciones e importación CSV.
 */
class ProyeccionVentasController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ImportacionVentasService $importacionService,
        private ProyeccionService $proyeccionService,
        private FormateoProyeccionService $formateoService
    ) {
    }

    /**
     * Obtiene los permisos del usuario para las proyecciones.
     */
    private function getUserPermissions($user): array
    {
        return [
            'canCreate' => $user?->can('create', ProyeccionVenta::class) ?? false,
            'canEdit' => $user?->can('proyecciones.edit') ?? false,
            'canDelete' => $user?->can('proyecciones.delete') ?? false,
        ];
    }

    /**
     * Genera y transmite una plantilla CSV para la carga de datos.
     */
    public function descargarPlantilla(): StreamedResponse
    {
        $this->authorize('create', ProyeccionVenta::class);

        return $this->importacionService->descargarPlantillaCSV();
    }

    /**
     * Importar datos históricos desde un archivo CSV.
     */
    public function importarCSV(Request $request, $empresa): RedirectResponse
    {
        $this->authorize('create', ProyeccionVenta::class);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ], [
            'csv_file.required' => 'Debe seleccionar un archivo CSV.',
            'csv_file.mimes' => 'El archivo debe ser de tipo CSV o TXT.',
            'csv_file.max' => 'El archivo no debe superar 2MB.',
        ]);

        try {
            [$insertadas, $actualizadas] = $this->importacionService->importarDesdeCSV(
                $request->file('csv_file'),
                $empresa
            );

            return redirect()->route('dashboard.proyecciones', $empresa)
                ->with('success', "Importación exitosa: {$insertadas} filas insertadas, {$actualizadas} filas actualizadas.");

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            return back()->withErrors([
                'csv_file' => 'Error interno al procesar el archivo. Por favor, intente nuevamente.',
            ]);
        }
    }

    /**
     * Muestra el dashboard con los datos históricos.
     */
    public function dashboard(Request $request, $empresa): Response
    {
        $this->authorize('viewAny', ProyeccionVenta::class);

        $datosVentaHistorico = DatoVentaHistorico::query()
            ->byEmpresa($empresa)
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        return Inertia::render('ProyeccionVentas/dashboard-proyeccion-ventas', [
            'datosVentaHistorico' => $datosVentaHistorico,
            'permissions' => $this->getUserPermissions($request->user()),
            'empresaId' => $empresa,
        ]);
    }

    /**
     * Genera las proyecciones de ventas usando los tres métodos
     * y las formatea para ser consumidas por el frontend.
     */
    public function generar(Request $request, $empresa): Response|RedirectResponse
    {
        $this->authorize('viewAny', ProyeccionVenta::class);

        $datosVentaHistoricos = DatoVentaHistorico::query()
            ->byEmpresa($empresa)
            ->orderBy('anio')
            ->orderBy('mes')
            ->get();

        if ($datosVentaHistoricos->count() < 2) {
            return redirect()
                ->route('dashboard.proyecciones', $empresa)
                ->with('error', 'Se requieren al menos 2 períodos de datos históricos para generar proyecciones.');
        }

        try {
            $datosTransformados = $this->formateoService->transformarParaCalculo($datosVentaHistoricos);

            $proyMinimos = $this->proyeccionService->calcularMinimosCuadrados($datosTransformados);
            $proyAbsoluto = $this->proyeccionService->calcularIncrementoAbsoluto($datosTransformados);
            $proyPorcentual = $this->proyeccionService->calcularIncrementoPorcentual($datosTransformados);

            $datosFormateados = $this->formateoService->formatearTodoParaVista(
                $datosVentaHistoricos,
                $proyMinimos,
                $proyAbsoluto,
                $proyPorcentual
            );

            return Inertia::render('ProyeccionVentas/resultados-proyeccion-ventas', [
                'empresaId' => $empresa,
                'permissions' => $this->getUserPermissions($request->user()),
                'serie' => $datosFormateados['serie'],
                'datosHistoricos' => $datosFormateados['datosHistoricos'],
                'datosProyecciones' => $datosFormateados['proyecciones'],
            ]);

        } catch (\InvalidArgumentException | \RuntimeException $e) {
            return redirect()
                ->route('dashboard.proyecciones', $empresa)
                ->with('error', 'Error al calcular proyecciones: ' . $e->getMessage());
        }
    }
}
