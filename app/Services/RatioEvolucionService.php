<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\RatioCalculado;
use App\Models\RatioSector;
use App\Traits\TieneRatiosFinancieros;
use Illuminate\Support\Facades\DB;

class RatioEvolucionService
{
    use TieneRatiosFinancieros;

    /**
     * Obtiene la evolución temporal de todos los ratios de una empresa
     */
    public function obtenerEvolucionCompleta(int $empresaId): array
    {
        $empresa = Empresa::with('sector')->findOrFail($empresaId);

        $aniosDisponibles = RatioCalculado::porEmpresa($empresaId)
            ->distinct()
            ->pluck('anio')
            ->sort()
            ->values()
            ->all(); // Convertir a array

        if (empty($aniosDisponibles)) {
            return [];
        }

        $ratiosDisponibles = self::obtenerNombresRatios();
        $evolucionPorRatio = [];

        foreach ($ratiosDisponibles as $claveRatio => $nombreRatio) {
            $evolucion = $this->obtenerEvolucionRatio(
                $empresaId,
                $claveRatio,
                $empresa->sector_id
            );

            // Solo incluir ratios que tienen datos
            if (!empty($evolucion)) {
                $evolucionPorRatio[$claveRatio] = $evolucion;
            }
        }

        return [
            'empresa' => [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre,
                'sector' => $empresa->sector->nombre ?? 'Sin sector',
            ],
            'anios_disponibles' => $aniosDisponibles,
            'ratios' => $evolucionPorRatio,
        ];
    }

    /**
     * Obtiene evolución de un ratio específico con benchmarks y promedios
     */
    public function obtenerEvolucionRatio(int $empresaId, string $nombreRatio, ?int $sectorId = null): array
    {
        if (!self::esRatioValido($nombreRatio)) {
            return [];
        }

        if (!$sectorId) {
            $empresa = Empresa::findOrFail($empresaId);
            $sectorId = $empresa->sector_id;
        }

        // Datos históricos de la empresa
        $datosEmpresa = RatioCalculado::porEmpresa($empresaId)
            ->porRatio($nombreRatio)
            ->orderBy('anio')
            ->get(['anio', 'valor_ratio'])
            ->map(fn($item) => [
                'anio' => $item->anio,
                'valor' => (float) $item->valor_ratio,
            ])->all();

        if (empty($datosEmpresa)) {
            return [];
        }

        // Benchmark del sector (valor constante)
        $benchmark = RatioSector::porSector($sectorId)
            ->porRatio($nombreRatio)
            ->first();

        // Promedios anuales del sector
        $promediosPorAnio = $this->calcularPromediosAnualesSector(
            $sectorId,
            $nombreRatio,
            collect($datosEmpresa)->pluck('anio')->all()
        );

        return [
            'nombre_ratio' => self::$nombresRatios[$nombreRatio],
            'clave_ratio' => $nombreRatio,
            'formula' => self::$formulasRatios[$nombreRatio],
            'categoria' => $this->categoriaRatio($nombreRatio),
            'serie_empresa' => $datosEmpresa,
            'benchmark_sector' => $benchmark ? [
                'valor' => (float) $benchmark->valor_referencia,
                'fuente' => $benchmark->fuente,
            ] : null,
            'promedios_sector' => $promediosPorAnio,
            'tendencia' => $this->calcularTendencia($datosEmpresa),
        ];
    }

    private function calcularPromediosAnualesSector(int $sectorId, string $nombreRatio, array $anios): array
    {
        if (empty($anios)) {
            return [];
        }

        $empresasSector = Empresa::where('sector_id', $sectorId)->pluck('id');

        return DB::table('ratios_calculados')
            ->whereIn('empresa_id', $empresasSector)
            ->where('nombre_ratio', $nombreRatio)
            ->whereIn('anio', $anios)
            ->groupBy('anio')
            ->select([
                'anio',
                DB::raw('AVG(valor_ratio) as promedio'),
            ])
            ->get()
            ->map(fn($item) => [
                'anio' => $item->anio,
                'valor' => round($item->promedio, 4),
            ])->all();
    }

    private function calcularTendencia(array $datos): array
    {
        if (count($datos) < 2) {
            return ['direccion' => 'sin_datos', 'variacion' => 0];
        }

        $primero = $datos[0]['valor'];
        $ultimo = end($datos)['valor'];
        $variacion = $primero != 0 ? (($ultimo - $primero) / abs($primero)) * 100 : 0;

        return [
            'direccion' => match(true) {
                $variacion > 5 => 'ascendente',
                $variacion < -5 => 'descendente',
                default => 'estable',
            },
            'variacion' => round($variacion, 2),
            'valor_inicial' => $primero,
            'valor_final' => $ultimo,
        ];
    }

    private function categoriaRatio(string $nombreRatio): string
    {
        return match($nombreRatio) {
            self::RAZON_CIRCULANTE, self::PRUEBA_ACIDA, self::CAPITAL_TRABAJO => 'Liquidez',
            self::ROTACION_INVENTARIO, self::DIAS_INVENTARIO, self::ROTACION_ACTIVOS => 'Actividad',
            self::GRADO_ENDEUDAMIENTO, self::ENDEUDAMIENTO_PATRIMONIAL => 'Endeudamiento',
            self::ROE, self::ROA => 'Rentabilidad',
            default => 'Otros',
        };
    }
}
