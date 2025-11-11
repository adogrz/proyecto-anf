<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\RatioCalculado;
use App\Traits\TieneRatiosFinancieros;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RatioPromedioService
{
    use TieneRatiosFinancieros;

    private const EPSILON = 1e-9;

    /**
     * Compara ratios de empresa con promedios del sector
     */
    public function compararConPromedioSector(int $empresaId, int $anio): array
    {
        $empresa = Empresa::with('sector')->findOrFail($empresaId);

        $ratiosEmpresa = RatioCalculado::porEmpresa($empresaId)
            ->porAnio($anio)
            ->get()
            ->keyBy('nombre_ratio');

        if ($ratiosEmpresa->isEmpty()) {
            return [];
        }

        $promedios = $this->calcularPromediosPorRatio($empresa->sector_id, $anio);

        return $ratiosEmpresa->map(function ($ratio) use ($promedios) {
            $promedio = $promedios->get($ratio->nombre_ratio);
            $promedioValido = $promedio && $this->esPromedioValido($promedio);

            return [
                'nombre_ratio' => $ratio->nombre_amigable,
                'clave_ratio' => $ratio->nombre_ratio,
                'valor_empresa' => (float)$ratio->valor_ratio,
                'formula' => $ratio->formula,
                'categoria' => $this->categoriaRatio($ratio->nombre_ratio),
                'promedio_sector' => $promedioValido ? [
                    'valor' => $promedio['promedio'],
                    'cantidad_empresas' => $promedio['cantidad'],
                    'minimo' => $promedio['minimo'],
                    'maximo' => $promedio['maximo'],
                    'diferencia' => $this->calcularDiferencia($ratio->valor_ratio, $promedio['promedio']),
                    'posicion_relativa' => $this->calcularPosicion($ratio->valor_ratio, $promedio['promedio']),
                    'interpretacion' => $this->interpretarPromedios($ratio->nombre_ratio, $ratio->valor_ratio, $promedio['promedio']),
                ] : null,
                'sin_referencia' => !$promedioValido,
            ];
        })->values()->all();
    }

    private function esPromedioValido(array $promedio): bool
    {
        // Considerar inválido si solo hay una empresa o el promedio es 0 y min/max también 0
        if (($promedio['cantidad'] ?? 0) < 2) return false;
        if (abs($promedio['promedio']) < self::EPSILON && abs($promedio['minimo']) < self::EPSILON && abs($promedio['maximo']) < self::EPSILON) {
            return false;
        }
        return true;
    }

    private function calcularPromediosPorRatio(int $sectorId, int $anio): Collection
    {
        $empresasSector = Empresa::where('sector_id', $sectorId)->pluck('id');

        return DB::table('ratios_calculados')
            ->whereIn('empresa_id', $empresasSector)
            ->where('anio', $anio)
            ->where('valor_ratio', '<>', 0)
            ->groupBy('nombre_ratio')
            ->select([
                'nombre_ratio',
                DB::raw('AVG(valor_ratio) as promedio'),
                DB::raw('COUNT(DISTINCT empresa_id) as cantidad'),
                DB::raw('MIN(valor_ratio) as minimo'),
                DB::raw('MAX(valor_ratio) as maximo'),
            ])
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->nombre_ratio => [
                    'promedio' => round($item->promedio, 4),
                    'cantidad' => (int)$item->cantidad,
                    'minimo' => round($item->minimo, 4),
                    'maximo' => round($item->maximo, 4),
                ]];
            });
    }

    private function calcularDiferencia(float $valorEmpresa, float $promedio): array
    {
        if (abs($promedio) < self::EPSILON) {
            return [
                'absoluta' => round($valorEmpresa, 4),
                'porcentual' => 0.0, // no se puede establecer porcentaje relativo
            ];
        }
        $diferencia = $valorEmpresa - $promedio;
        $porcentaje = ($diferencia / abs($promedio)) * 100;

        return [
            'absoluta' => round($diferencia, 4),
            'porcentual' => round($porcentaje, 2),
        ];
    }

    private function calcularPosicion(float $valorEmpresa, float $promedio): string
    {
        // Evitar división por cero cuando no hay datos suficientes o el promedio del sector es 0
        if (abs($promedio) < self::EPSILON) {
            if (abs($valorEmpresa) < self::EPSILON) {
                return 'En el promedio';
            }
            // Si el promedio es 0, usar solo el signo del valor de la empresa
            return $valorEmpresa > 0 ? 'Superior' : 'Inferior';
        }

        $diferencia = (($valorEmpresa - $promedio) / abs($promedio)) * 100;

        return match (true) {
            abs($diferencia) < 5 => 'En el promedio',
            $diferencia > 20 => 'Muy superior',
            $diferencia > 5 => 'Superior',
            $diferencia < -20 => 'Muy inferior',
            $diferencia < -5 => 'Inferior',
            default => 'En el promedio',
        };
    }

    private function interpretarPromedios(string $nombreRatio, float $valorEmpresa, float $promedio): string
    {
        if (abs($promedio) < self::EPSILON) {
            return 'Sin suficiente información sectorial para este ratio';
        }

        $diferencia = $this->calcularDiferencia($valorEmpresa, $promedio);

        if (abs($diferencia['porcentual']) < 5) {
            return 'Rendimiento similar al promedio del sector';
        }

        $esMenorMejor = in_array($nombreRatio, [
            self::GRADO_ENDEUDAMIENTO,
            self::ENDEUDAMIENTO_PATRIMONIAL,
            self::DIAS_INVENTARIO,
        ]);

        $esMayor = $diferencia['absoluta'] > 0;

        if ($esMenorMejor) {
            return $esMayor
                ? 'Por encima del promedio sectorial. Considere optimizar.'
                : 'Por debajo del promedio sectorial. Desempeño favorable.';
        }

        return $esMayor
            ? 'Por encima del promedio sectorial. Buen desempeño.'
            : 'Por debajo del promedio sectorial. Área de mejora.';
    }

    private function categoriaRatio(string $nombreRatio): string
    {
        return match ($nombreRatio) {
            self::RAZON_CIRCULANTE, self::PRUEBA_ACIDA, self::CAPITAL_TRABAJO => 'Liquidez',
            self::ROTACION_INVENTARIO, self::DIAS_INVENTARIO, self::ROTACION_ACTIVOS => 'Actividad',
            self::GRADO_ENDEUDAMIENTO, self::ENDEUDAMIENTO_PATRIMONIAL => 'Endeudamiento',
            self::ROE, self::ROA => 'Rentabilidad',
            default => 'Otros',
        };
    }
}
