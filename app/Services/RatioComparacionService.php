<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\RatioCalculado;
use App\Models\RatioSector;
use App\Traits\TieneRatiosFinancieros;
use Illuminate\Support\Collection;

class RatioComparacionService
{
    use TieneRatiosFinancieros;

    /**
     * Compara ratios de una empresa con benchmarks del sector
     */
    public function compararConBenchmark(int $empresaId, int $anio): array
    {
        $empresa = Empresa::with('sector')->findOrFail($empresaId);

        $ratiosEmpresa = RatioCalculado::porEmpresa($empresaId)
            ->porAnio($anio)
            ->get()
            ->keyBy('nombre_ratio');

        if ($ratiosEmpresa->isEmpty()) {
            return [];
        }

        $benchmarks = RatioSector::porSector($empresa->sector_id)
            ->get()
            ->keyBy('nombre_ratio');

        return $this->generarComparaciones($ratiosEmpresa, $benchmarks);
    }

    private function generarComparaciones(Collection $ratiosEmpresa, Collection $benchmarks): array
    {
        return $ratiosEmpresa->map(function ($ratio) use ($benchmarks) {
            $benchmark = $benchmarks->get($ratio->nombre_ratio);

            return [
                'nombre_ratio' => $ratio->nombre_amigable,
                'clave_ratio' => $ratio->nombre_ratio,
                'valor_empresa' => (float) $ratio->valor_ratio,
                'formula' => $ratio->formula,
                'categoria' => $this->categoriaRatio($ratio->nombre_ratio),
                'benchmark' => $benchmark ? [
                    'valor' => (float) $benchmark->valor_referencia,
                    'fuente' => $benchmark->fuente,
                    'diferencia' => $this->calcularDiferencia($ratio->valor_ratio, $benchmark->valor_referencia),
                    'cumple' => $this->evaluar($ratio->nombre_ratio, $ratio->valor_ratio, $benchmark->valor_referencia),
                    'estado' => $this->determinarEstado($ratio->nombre_ratio, $ratio->valor_ratio, $benchmark->valor_referencia),
                    'interpretacion' => $this->interpretar($ratio->nombre_ratio, $ratio->valor_ratio, $benchmark->valor_referencia),
                ] : null,
            ];
        })->values()->all();
    }

    private function calcularDiferencia(float $valorEmpresa, float $valorReferencia): array
    {
        $diferencia = $valorEmpresa - $valorReferencia;
        $porcentaje = $valorReferencia != 0
            ? ($diferencia / abs($valorReferencia)) * 100
            : 0;

        return [
            'absoluta' => round($diferencia, 4),
            'porcentual' => round($porcentaje, 2),
        ];
    }

    private function evaluar(string $nombreRatio, float $valorEmpresa, float $valorReferencia): bool
    {
        $ratiosMenorMejor = [
            self::GRADO_ENDEUDAMIENTO,
            self::ENDEUDAMIENTO_PATRIMONIAL,
            self::DIAS_INVENTARIO,
        ];

        return in_array($nombreRatio, $ratiosMenorMejor)
            ? $valorEmpresa <= $valorReferencia
            : $valorEmpresa >= $valorReferencia;
    }

    private function determinarEstado(string $nombreRatio, float $valorEmpresa, float $valorReferencia): string
    {
        $diferencia = $this->calcularDiferencia($valorEmpresa, $valorReferencia);
        $absDiferencia = abs($diferencia['porcentual']);

        $esMenorMejor = in_array($nombreRatio, [
            self::GRADO_ENDEUDAMIENTO,
            self::ENDEUDAMIENTO_PATRIMONIAL,
            self::DIAS_INVENTARIO,
        ]);

        if ($absDiferencia < 2) return 'neutral';

        $esMayor = $diferencia['absoluta'] > 0;

        if ($esMenorMejor) {
            if ($esMayor) return $absDiferencia > 15 ? 'danger' : 'warning';
            return $absDiferencia > 15 ? 'success' : 'info';
        }

        if ($esMayor) return $absDiferencia > 15 ? 'success' : 'info';
        return $absDiferencia > 15 ? 'danger' : 'warning';
    }

    private function interpretar(string $nombreRatio, float $valorEmpresa, float $valorReferencia): string
    {
        $diferencia = $this->calcularDiferencia($valorEmpresa, $valorReferencia);

        if (abs($diferencia['porcentual']) < 2) {
            return 'Prácticamente igual al benchmark del sector';
        }

        $esMenorMejor = in_array($nombreRatio, [
            self::GRADO_ENDEUDAMIENTO,
            self::ENDEUDAMIENTO_PATRIMONIAL,
            self::DIAS_INVENTARIO,
        ]);

        $esMayor = $diferencia['absoluta'] > 0;

        if ($esMenorMejor) {
            return $esMayor
                ? 'Por encima del benchmark. Se recomienda reducir este indicador.'
                : 'Por debajo del benchmark. Posición favorable.';
        }

        return $esMayor
            ? 'Supera el benchmark. Desempeño positivo.'
            : 'Por debajo del benchmark. Se recomienda mejorar.';
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
