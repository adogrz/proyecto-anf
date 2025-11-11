<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\RatioCalculado;
use App\Traits\TieneRatiosFinancieros;

class AnalisisRatiosDashboardService
{
    use TieneRatiosFinancieros;

    public function __construct(
        private RatioComparacionService $comparacionService,
        private RatioPromedioService $promedioService,
        private RatioEvolucionService $evolucionService
    ) {}

    /**
     * Obtiene todos los datos necesarios para el dashboard de análisis de ratios
     */
    public function obtenerDatosDashboard(int $empresaId, int $anio): array
    {
        $empresa = Empresa::with('sector')->findOrFail($empresaId);

        // Obtener años disponibles con ratios calculados
        $aniosDisponibles = RatioCalculado::porEmpresa($empresaId)
            ->distinct()
            ->pluck('anio')
            ->sort()
            ->values()
            ->all();

        if (empty($aniosDisponibles)) {
            return [
                'empresa' => [
                    'id' => $empresa->id,
                    'nombre' => $empresa->nombre,
                    'sector' => $empresa->sector?->nombre ?? 'Sin sector',
                    'sector_id' => $empresa->sector_id,
                ],
                'anios_disponibles' => [],
                'anio_seleccionado' => now()->year,
                'tiene_datos' => false,
                'metricas_resumen' => [
                    'total_ratios' => 0,
                    'mejor_categoria' => null,
                    'categoria_oportunidad' => null,
                    'mejor_mejora' => null,
                ],
                'preview_benchmark' => [
                    'ratios_cumplen' => 0,
                    'total_ratios' => 0,
                    'porcentaje' => 0,
                ],
                'preview_promedio' => [
                    'superiores' => 0,
                    'total_categorias' => 0,
                ],
                'preview_evolucion' => [
                    'anios_datos' => 0,
                    'tendencia_general' => 'sin_datos',
                ],
            ];
        }

        // Si el año seleccionado no tiene datos, usar el más reciente
        if (!in_array($anio, $aniosDisponibles)) {
            $anio = max($aniosDisponibles);
        }

        // Obtener comparaciones con benchmark
        $comparacionesBenchmark = $this->comparacionService->compararConBenchmark($empresaId, $anio);

        // Obtener comparaciones con promedio
        $comparacionesPromedio = $this->promedioService->compararConPromedioSector($empresaId, $anio);

        // Obtener datos de evolución
        $evolucionCompleta = $this->evolucionService->obtenerEvolucionCompleta($empresaId);

        return [
            'empresa' => [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre,
                'sector' => $empresa->sector?->nombre ?? 'Sin sector',
                'sector_id' => $empresa->sector_id,
            ],
            'anios_disponibles' => $aniosDisponibles,
            'anio_seleccionado' => $anio,
            'tiene_datos' => !empty($comparacionesBenchmark),
            'metricas_resumen' => $this->calcularMetricasResumen(
                $comparacionesBenchmark,
                $comparacionesPromedio,
                $evolucionCompleta
            ),
            'preview_benchmark' => $this->generarPreviewBenchmark($comparacionesBenchmark),
            'preview_promedio' => $this->generarPreviewPromedio($comparacionesPromedio),
            'preview_evolucion' => $this->generarPreviewEvolucion($evolucionCompleta),
        ];
    }

    /**
     * Calcula las métricas de resumen ejecutivo
     */
    private function calcularMetricasResumen(array $benchmark, array $promedio, array $evolucion): array
    {
        $metricas = [
            'total_ratios' => count($benchmark),
            'mejor_categoria' => null,
            'categoria_oportunidad' => null,
            'mejor_mejora' => null,
        ];

        if (empty($benchmark)) {
            return $metricas;
        }

        // Agrupar por categoría y calcular cumplimiento
        $porCategoria = collect($benchmark)->groupBy('categoria');
        $cumplimientoPorCategoria = [];

        foreach ($porCategoria as $categoria => $ratios) {
            $cumplimientos = collect($ratios)->filter(fn($r) => $r['benchmark']['cumple'] ?? false)->count();
            $total = count($ratios);
            $cumplimientoPorCategoria[$categoria] = [
                'cumplimientos' => $cumplimientos,
                'total' => $total,
                'porcentaje' => $total > 0 ? ($cumplimientos / $total) * 100 : 0,
            ];
        }

        // Mejor categoría
        $mejorCategoria = collect($cumplimientoPorCategoria)->sortByDesc('porcentaje')->first();
        if ($mejorCategoria) {
            $metricas['mejor_categoria'] = [
                'nombre' => collect($cumplimientoPorCategoria)->sortByDesc('porcentaje')->keys()->first(),
                'porcentaje' => round($mejorCategoria['porcentaje'], 1),
            ];
        }

        // Categoría con oportunidad (peor)
        $peorCategoria = collect($cumplimientoPorCategoria)->sortBy('porcentaje')->first();
        if ($peorCategoria) {
            $metricas['categoria_oportunidad'] = [
                'nombre' => collect($cumplimientoPorCategoria)->sortBy('porcentaje')->keys()->first(),
                'porcentaje' => round($peorCategoria['porcentaje'], 1),
            ];
        }

        // Mejor mejora temporal (si hay datos de evolución)
        if (!empty($evolucion['ratios'] ?? [])) {
            $mejorMejora = collect($evolucion['ratios'])
                ->filter(fn($r) => ($r['tendencia']['direccion'] ?? '') === 'ascendente')
                ->filter(fn($r) => isset($r['tendencia']['variacion'])) // Asegurar que existe la clave
                ->sortByDesc(fn($r) => abs($r['tendencia']['variacion']))
                ->first();

            if ($mejorMejora && isset($mejorMejora['tendencia']['variacion'])) {
                $metricas['mejor_mejora'] = [
                    'nombre' => $mejorMejora['nombre_ratio'],
                    'variacion' => round($mejorMejora['tendencia']['variacion'], 1),
                ];
            }
        }

        return $metricas;
    }

    /**
     * Genera preview para la card de benchmark
     */
    private function generarPreviewBenchmark(array $comparaciones): array
    {
        if (empty($comparaciones)) {
            return [
                'ratios_cumplen' => 0,
                'total_ratios' => 0,
                'porcentaje' => 0,
            ];
        }

        $cumplimientos = collect($comparaciones)->filter(fn($r) => $r['benchmark']['cumple'] ?? false)->count();
        $total = count($comparaciones);

        return [
            'ratios_cumplen' => $cumplimientos,
            'total_ratios' => $total,
            'porcentaje' => $total > 0 ? round(($cumplimientos / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Genera preview para la card de promedio
     */
    private function generarPreviewPromedio(array $comparaciones): array
    {
        if (empty($comparaciones)) {
            return [
                'superiores' => 0,
                'total_categorias' => 0,
            ];
        }

        // Agrupar por categoría y ver si está por encima del promedio
        $porCategoria = collect($comparaciones)->groupBy('categoria');
        $categoriasSuperiores = 0;

        foreach ($porCategoria as $ratios) {
            $superiores = collect($ratios)->filter(function ($r) {
                $posicion = $r['promedio_sector']['posicion_relativa'] ?? '';
                return in_array($posicion, ['Superior', 'Muy superior']);
            })->count();

            if ($superiores > count($ratios) / 2) {
                $categoriasSuperiores++;
            }
        }

        return [
            'superiores' => $categoriasSuperiores,
            'total_categorias' => $porCategoria->count(),
        ];
    }

    /**
     * Genera preview para la card de evolución
     */
    private function generarPreviewEvolucion(array $evolucion): array
    {
        if (empty($evolucion['anios_disponibles'] ?? [])) {
            return [
                'anios_datos' => 0,
                'tendencia_general' => 'sin_datos',
            ];
        }

        $ratios = $evolucion['ratios'] ?? [];
        $tendencias = collect($ratios)->pluck('tendencia.direccion')->countBy();

        $tendenciaGeneral = 'estable';
        if (($tendencias['ascendente'] ?? 0) > ($tendencias['descendente'] ?? 0)) {
            $tendenciaGeneral = 'ascendente';
        } elseif (($tendencias['descendente'] ?? 0) > ($tendencias['ascendente'] ?? 0)) {
            $tendenciaGeneral = 'descendente';
        }

        return [
            'anios_datos' => count($evolucion['anios_disponibles']),
            'tendencia_general' => $tendenciaGeneral,
        ];
    }
}

