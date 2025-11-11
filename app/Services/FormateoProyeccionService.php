<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio para formatear datos de proyecciones de ventas
 * para ser consumidos por el frontend (gráficos y tablas).
 */
class FormateoProyeccionService
{
    /**
     * Nombres abreviados de los meses en español.
     */
    private const MESES = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    /**
     * Obtiene el nombre abreviado del mes en español.
     *
     * @param int $mesNumero (1 para Enero, 12 para Diciembre)
     * @return string
     */
    private function obtenerNombreMes(int $mesNumero): string
    {
        return self::MESES[$mesNumero] ?? (string)$mesNumero;
    }

    /**
     * Formatea un año a dos dígitos de forma consistente.
     *
     * @param int $anio
     * @return string
     */
    private function formatearAnio(int $anio): string
    {
        return substr((string)$anio, -2);
    }

    /**
     * Crea la etiqueta de período (ej: "Ene 25").
     *
     * @param int $mes
     * @param int $anio
     * @return string
     */
    private function crearEtiquetaPeriodo(int $mes, int $anio): string
    {
        return $this->obtenerNombreMes($mes) . ' ' . $this->formatearAnio($anio);
    }

    /**
     * Formatea la colección de datos históricos de Eloquent 
     * al formato requerido por el gráfico.
     *
     * @param Collection $datosHistoricos Colección de Eloquent de DatoVentaHistorico
     * @return array
     */
    public function formatearHistoricosParaGrafico(Collection $datosHistoricos): array
    {
        return $datosHistoricos->map(function ($dato) {
            return [
                'periodoLabel' => $this->crearEtiquetaPeriodo($dato->mes, $dato->anio),
                'historico'    => (float) $dato->monto,
                'minimos'      => null,
                'absoluto'     => null,
                'porcentual'   => null,
            ];
        })->values()->all();
    }

    /**
     * Formatea los arrays de resultados de las proyecciones 
     * al formato requerido por el gráfico.
     *
     * @param Collection $datosHistoricos Se usa para saber la fecha de inicio
     * @param array $proyMinimos Array de ['x' => int, 'monto' => float]
     * @param array $proyAbsoluto Array de ['x' => int, 'monto' => float]
     * @param array $proyPorcentual Array de ['x' => int, 'monto' => float]
     * @return array
     */
    public function formatearProyeccionesParaGrafico(
        Collection $datosHistoricos,
        array $proyMinimos,
        array $proyAbsoluto,
        array $proyPorcentual
    ): array {
        $proyeccionesFormateadas = [];

        // Obtenemos el último período histórico para saber dónde empezar
        $ultimoDato = $datosHistoricos->last();
        $mesActual = $ultimoDato->mes;
        $anioActual = $ultimoDato->anio;

        // Iteramos 12 veces (uno por cada mes de proyección)
        for ($i = 0; $i < 12; $i++) {
            // Calculamos el siguiente período
            $mesActual++;
            if ($mesActual > 12) {
                $mesActual = 1;
                $anioActual++;
            }

            // Construimos el objeto para el gráfico
            $proyeccionesFormateadas[] = [
                'periodoLabel' => $this->crearEtiquetaPeriodo($mesActual, $anioActual),
                'historico'    => null,
                'minimos'      => (float) $proyMinimos[$i]['monto'],
                'absoluto'     => (float) $proyAbsoluto[$i]['monto'],
                'porcentual'   => (float) $proyPorcentual[$i]['monto'],
            ];
        }

        return $proyeccionesFormateadas;
    }

    /**
     * Transforma los datos de Eloquent al formato esperado por el servicio de proyección.
     *
     * @param Collection $datosHistoricos
     * @return array Array de ['x' => int, 'y' => float]
     */
    public function transformarParaCalculo(Collection $datosHistoricos): array
    {
        $datosTransformados = [];
        $periodo = 1;

        foreach ($datosHistoricos as $dato) {
            $datosTransformados[] = [
                'x' => $periodo++,
                'y' => (float) $dato->monto,
            ];
        }

        return $datosTransformados;
    }

    /**
     * Unifica los datos históricos y las proyecciones en un único dataset
     * para ser consumido por gráficos como Recharts.
     *
     * @param array $datosHistoricosFormateados
     * @param array $proyeccionesFormateadas
     * @return array
     */
    public function unificarSerie(array $datosHistoricosFormateados, array $proyeccionesFormateadas): array
    {
        return array_merge($datosHistoricosFormateados, $proyeccionesFormateadas);
    }

    /**
     * Método principal que orquesta todo el formateo de datos para la vista.
     *
     * @param Collection $datosHistoricos
     * @param array $proyMinimos
     * @param array $proyAbsoluto
     * @param array $proyPorcentual
     * @return array Array con ['serie' => [...], 'datosHistoricos' => [...], 'proyecciones' => [...]]
     */
    public function formatearTodoParaVista(
        Collection $datosHistoricos,
        array $proyMinimos,
        array $proyAbsoluto,
        array $proyPorcentual
    ): array {
        $datosHistoricosFormateados = $this->formatearHistoricosParaGrafico($datosHistoricos);
        $proyeccionesFormateadas = $this->formatearProyeccionesParaGrafico(
            $datosHistoricos,
            $proyMinimos,
            $proyAbsoluto,
            $proyPorcentual
        );

        return [
            'serie' => $this->unificarSerie($datosHistoricosFormateados, $proyeccionesFormateadas),
            'datosHistoricos' => $datosHistoricosFormateados,
            'proyecciones' => $proyeccionesFormateadas,
        ];
    }
}
