<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\RatioCalculado;
use App\Services\RatioComparacionService;
use App\Services\RatioPromedioService;
use App\Services\RatioEvolucionService;
use App\Services\AnalisisRatiosDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalisisRatiosController extends Controller
{
    public function __construct(
        private RatioComparacionService $comparacionService,
        private RatioPromedioService $promedioService,
        private RatioEvolucionService $evolucionService,
        private AnalisisRatiosDashboardService $dashboardService
    ) {}

    /**
     * Dashboard central de análisis de ratios
     * Ruta: /empresas/{empresa}/analisis/ratios
     */
    public function dashboard(Request $request, int $empresaId): Response
    {
        $empresa = Empresa::with('sector')->findOrFail($empresaId);

        // Obtener el año más reciente con datos por defecto
        $anioSeleccionado = RatioCalculado::porEmpresa($empresaId)
            ->max('anio');

        // Si se especifica un año en la query, usarlo
        if ($request->has('anio')) {
            $anioSeleccionado = (int) $request->query('anio');
        }

        // Si no hay año seleccionado, usar el año actual
        if (!$anioSeleccionado) {
            $anioSeleccionado = now()->year;
        }

        $datos = $this->dashboardService->obtenerDatosDashboard($empresaId, $anioSeleccionado);

        return Inertia::render('Analisis/Ratios/Dashboard', [
            ...$datos,
            'permissions' => $this->getUserPermissions($request->user()),
        ]);
    }

    /**
     * Comparación con benchmarks del sector
     * Ruta: /empresas/{empresa}/analisis/ratios/benchmark/{anio}
     */
    public function compararConBenchmark(Request $request, int $empresaId, int $anio): Response
    {
        $empresa = Empresa::with('sector')->findOrFail($empresaId);
        $comparaciones = $this->comparacionService->compararConBenchmark($empresaId, $anio);

        if (empty($comparaciones)) {
            return Inertia::render('Analisis/Ratios/SinDatos', [
                'empresa' => $empresa,
                'anio' => $anio,
                'mensaje' => 'No hay datos de ratios calculados para este año',
            ]);
        }

        // Agrupar por categoría
        $porCategoria = collect($comparaciones)->groupBy('categoria')->all();

        return Inertia::render('Analisis/Ratios/ComparacionBenchmark', [
            'empresa' => $empresa,
            'anio' => $anio,
            'comparaciones' => $comparaciones,
            'comparaciones_por_categoria' => $porCategoria,
            'permissions' => $this->getUserPermissions($request->user()),
        ]);
    }

    /**
     * Comparación con promedio del sector
     * Ruta: /empresas/{empresa}/analisis/ratios/promedio-sector/{anio}
     */
    public function compararConPromedioSector(Request $request, int $empresaId, int $anio): Response
    {
        $empresa = Empresa::with('sector')->findOrFail($empresaId);
        $comparaciones = $this->promedioService->compararConPromedioSector($empresaId, $anio);

        if (empty($comparaciones)) {
            return Inertia::render('Analisis/Ratios/SinDatos', [
                'empresa' => $empresa,
                'anio' => $anio,
                'mensaje' => 'No hay datos de ratios calculados para este año',
            ]);
        }

        // Agrupar por categoría
        $porCategoria = collect($comparaciones)->groupBy('categoria')->all();

        return Inertia::render('Analisis/Ratios/ComparacionPromedio', [
            'empresa' => $empresa,
            'anio' => $anio,
            'comparaciones' => $comparaciones,
            'comparaciones_por_categoria' => $porCategoria,
            'permissions' => $this->getUserPermissions($request->user()),
        ]);
    }

    /**
     * Evolución temporal de ratios
     * Ruta: /empresas/{empresa}/analisis/ratios/evolucion
     */
    public function evolucionRatios(Request $request, int $empresaId): Response
    {
        $empresa = Empresa::with('sector')->findOrFail($empresaId);
        $evolucion = $this->evolucionService->obtenerEvolucionCompleta($empresaId);

        if (empty($evolucion)) {
            return Inertia::render('Analisis/Ratios/SinDatos', [
                'empresa' => $empresa,
                'mensaje' => 'No hay datos históricos de ratios para esta empresa',
            ]);
        }

        return Inertia::render('Analisis/Ratios/Evolucion', [
            'empresa' => [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre,
                'sector' => [
                    'nombre' => $empresa->sector?->nombre ?? 'Sin sector',
                ],
            ],
            'anios_disponibles' => $evolucion['anios_disponibles'] ?? [],
            'ratios' => $evolucion['ratios'] ?? [],
            'permissions' => $this->getUserPermissions($request->user()),
        ]);
    }

    private function getUserPermissions($user): array
    {
        return [
            'canViewAnalysis' => $user?->can('ratios-financieros.index') ?? false,
        ];
    }
}
