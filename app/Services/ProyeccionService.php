<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * Servicio para cálculos de proyecciones de ventas
 */
class ProyeccionService
{
    /**
     * Valida que los datos históricos tengan el formato correcto
     *
     * @param array $datos
     * @param int $minimoRegistros
     * @throws InvalidArgumentException
     */
    private function validarDatosHistoricos(array $datos, int $minimoRegistros = 2): void
    {
        if (count($datos) < $minimoRegistros) {
            throw new InvalidArgumentException("Se requieren al menos {$minimoRegistros} períodos de datos.");
        }

        foreach ($datos as $dato) {
            if (!isset($dato['x']) || !isset($dato['y'])) {
                throw new InvalidArgumentException("Cada elemento debe contener 'x' e 'y'.");
            }
        }
    }

    /**
     * Calcula proyección usando el método de mínimos cuadrados
     *
     * @param array $datosHistoricos Array de ['x' => int, 'y' => float]
     * @return array Array con ['x' => int, 'monto' => float] para los próximos 12 períodos
     */
    public function calcularMinimosCuadrados(array $datosHistoricos): array
    {
        $this->validarDatosHistoricos($datosHistoricos);

        $n = count($datosHistoricos);
        $sumX = $sumY = $sumXY = $sumX2 = 0;

        foreach ($datosHistoricos as $dato) {
            $x = (int)$dato['x'];
            $y = (float)$dato['y'];

            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x ** 2;
        }

        $denominador = ($n * $sumX2) - ($sumX ** 2);
        if ($denominador == 0) {
            throw new RuntimeException("Denominador cero: datos no válidos para regresión.");
        }

        $pendiente = (($n * $sumXY) - ($sumX * $sumY)) / $denominador;
        $interseccion = ($sumY - ($pendiente * $sumX)) / $n;

        // Obtener el último valor X para continuar la secuencia
        $ultimoX = max(array_column($datosHistoricos, 'x'));

        $proyecciones = [];

        for ($i = 1; $i <= 12; $i++) {
            $xProyectado = $ultimoX + $i;
            $valorProyectado = ($pendiente * $xProyectado) + $interseccion;

            $proyecciones[] = [
                'x' => $xProyectado,
                'monto' => max(0, round($valorProyectado, 2)),
            ];
        }

        return $proyecciones;
    }

    /**
     * Calcula proyección por incremento porcentual
     *
     * @param array $datosHistoricos Array de ['x' => int, 'y' => float]
     * @return array Array con ['x' => int, 'monto' => float] para los próximos 12 períodos
     */
    public function calcularIncrementoPorcentual(array $datosHistoricos): array
    {
        $this->validarDatosHistoricos($datosHistoricos);

        // Determinar el porcentaje de variaciones de los distintos períodos con respecto al anterior
        $porcentajeVariaciones = [];
        for ($i = 1; $i < count($datosHistoricos); $i++) {
            $yActual = (float)$datosHistoricos[$i]['y'];
            $yAnterior = (float)$datosHistoricos[$i - 1]['y'];
            if ($yAnterior != 0) {
                $porcentajeVariaciones[] = ($yActual - $yAnterior) / $yAnterior;
            }
        }

        // Validar que se pudieron calcular variaciones
        if (empty($porcentajeVariaciones)) {
            throw new RuntimeException("No se pudieron calcular variaciones porcentuales. Todos los valores anteriores son cero.");
        }

        // Determinar el promedio de los porcentajes de variación
        $promedioVariacion = array_sum($porcentajeVariaciones) / count($porcentajeVariaciones);

        $ultimoDato = end($datosHistoricos);
        $ultimoX = (int)$ultimoDato['x'];
        $valorActual = (float)$ultimoDato['y'];

        $proyecciones = [];
        for ($i = 1; $i <= 12; $i++) {
            $valorActual += ($valorActual * $promedioVariacion);

            $proyecciones[] = [
                'x' => $ultimoX + $i,
                'monto' => max(0, round($valorActual, 2)),
            ];
        }

        return $proyecciones;
    }

    /**
     * Calcula proyección por incremento absoluto
     *
     * @param array $datosHistoricos Array de ['x' => int, 'y' => float]
     * @return array Array con ['x' => int, 'monto' => float] para los próximos 12 períodos
     */
    public function calcularIncrementoAbsoluto(array $datosHistoricos): array
    {
        $this->validarDatosHistoricos($datosHistoricos);

        // Determinar las variaciones absolutas de los distintos períodos con respecto al anterior
        $variacionesAbsolutas = [];
        for ($i = 1; $i < count($datosHistoricos); $i++) {
            $yActual = (float)$datosHistoricos[$i]['y'];
            $yAnterior = (float)$datosHistoricos[$i - 1]['y'];
            $variacionesAbsolutas[] = $yActual - $yAnterior;
        }

        // Validar que se pudieron calcular variaciones
        if (empty($variacionesAbsolutas)) {
            throw new RuntimeException("No se pudieron calcular variaciones absolutas.");
        }

        // Determinar el promedio de las variaciones absolutas
        $promedioVariacion = array_sum($variacionesAbsolutas) / count($variacionesAbsolutas);

        $ultimoDato = end($datosHistoricos);
        $ultimoX = (int)$ultimoDato['x'];
        $valorActual = (float)$ultimoDato['y'];

        $proyecciones = [];
        for ($i = 1; $i <= 12; $i++) {
            $valorActual += $promedioVariacion;

            $proyecciones[] = [
                'x' => $ultimoX + $i,
                'monto' => max(0, round($valorActual, 2)),
            ];
        }

        return $proyecciones;
    }
}
