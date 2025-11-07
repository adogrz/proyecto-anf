<?php

namespace App\Services;

use App\Models\DatoVentaHistorico;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportacionVentasService
{
    /**
     * Importar datos desde un archivo CSV.
     *
     * @param UploadedFile $archivo
     * @param int $empresaId
     * @return array [insertadas, actualizadas]
     * @throws ValidationException
     */
    public function importarDesdeCSV(UploadedFile $archivo, int $empresaId): array
    {
        // Fase 1: Validación de sintaxis
        [$filasParaProcesar, $erroresSintaxis] = $this->validarSintaxisCSV($archivo);

        if (!empty($erroresSintaxis)) {
            throw ValidationException::withMessages([
                'csv_file' => 'El archivo contiene errores de formato.',
                'errores_fila' => $erroresSintaxis,
            ]);
        }

        // Fase 2: Validación de lógica y carga
        return $this->procesarDatosCSV($filasParaProcesar, $empresaId);
    }

    /**
     * Validar la sintaxis y formato del archivo CSV.
     *
     * @param UploadedFile $archivo
     * @return array [filasValidas, errores]
     * @throws ValidationException
     */
    private function validarSintaxisCSV(UploadedFile $archivo): array
    {
        $filasParaProcesar = [];
        $erroresSintaxis = [];
        $periodosVistos = [];
        $maxFilas = 1000;
        $filaActual = 0;

        $handle = fopen($archivo->getRealPath(), 'r');

        if ($handle === false) {
            throw new \Exception('No se pudo abrir el archivo CSV.');
        }

        // Leer y validar cabecera
        $cabeceras = fgetcsv($handle, 1000, ';');

        if (!$this->validarCabeceras($cabeceras)) {
            fclose($handle);
            throw ValidationException::withMessages([
                'csv_file' => 'Las cabeceras del archivo no son válidas. Deben ser: Anio, Mes, Monto_Venta'
            ]);
        }

        // Procesar cada fila
        while (($datos = fgetcsv($handle, 1000, ';')) !== false) {
            $filaActual++;

            // Validar límite de filas
            if ($filaActual > $maxFilas) {
                fclose($handle);
                throw ValidationException::withMessages([
                    'csv_file' => "El archivo excede el límite de {$maxFilas} filas."
                ]);
            }

            // Saltar filas vacías
            if (empty(array_filter($datos))) {
                continue;
            }

            // Validar número de columnas
            if (count($datos) !== 3) {
                $erroresSintaxis[] = [
                    'fila' => $filaActual,
                    'error' => "La fila debe tener exactamente 3 columnas (tiene " . count($datos) . ").",
                ];
                continue;
            }

            [$anio, $mes, $monto] = $datos;

            // Validar Año
            if (!is_numeric($anio) || (int)$anio != $anio || $anio < 2000 || $anio > 2100) {
                $erroresSintaxis[] = [
                    'fila' => $filaActual,
                    'error' => "Año inválido: '{$anio}'. Debe ser un número entero entre 2000 y 2100.",
                ];
                continue;
            }

            // Validar Mes
            if (!is_numeric($mes) || (int)$mes != $mes || $mes < 1 || $mes > 12) {
                $erroresSintaxis[] = [
                    'fila' => $filaActual,
                    'error' => "Mes inválido: '{$mes}'. Debe ser un número entero entre 1 y 12.",
                ];
                continue;
            }

            // Validar Monto
            $montoLimpio = str_replace(',', '.', $monto);
            if (!is_numeric($montoLimpio) || $montoLimpio < 0) {
                $erroresSintaxis[] = [
                    'fila' => $filaActual,
                    'error' => "Monto inválido: '{$monto}'. Debe ser un número mayor o igual a 0.",
                ];
                continue;
            }

            // Validar duplicados dentro del CSV
            $clavePeriodo = "{$anio}-{$mes}";
            if (isset($periodosVistos[$clavePeriodo])) {
                $erroresSintaxis[] = [
                    'fila' => $filaActual,
                    'error' => "Período duplicado: {$this->getMesNombre((int)$mes)} {$anio} (ya aparece en la fila {$periodosVistos[$clavePeriodo]}).",
                ];
                continue;
            }

            $periodosVistos[$clavePeriodo] = $filaActual;

            // Agregar a filas válidas
            $filasParaProcesar[] = [
                'anio' => (int)$anio,
                'mes' => (int)$mes,
                'monto' => (float)$montoLimpio,
                'fila_original' => $filaActual,
            ];
        }

        fclose($handle);

        return [$filasParaProcesar, $erroresSintaxis];
    }

    /**
     * Validar que las cabeceras del CSV sean correctas.
     *
     * @param array|null $cabeceras
     * @return bool
     */
    private function validarCabeceras(?array $cabeceras): bool
    {
        if ($cabeceras === null) {
            return false;
        }

        $esperadas = ['Anio', 'Mes', 'Monto_Venta'];
        return $cabeceras === $esperadas;
    }

    /**
     * Procesar y cargar los datos validados a la base de datos.
     *
     * @param array $filas
     * @param int $empresaId
     * @return array [insertadas, actualizadas]
     * @throws ValidationException
     */
    private function procesarDatosCSV(array $filas, int $empresaId): array
    {
        // Ordenar filas por año y mes
        usort($filas, function ($a, $b) {
            if ($a['anio'] === $b['anio']) {
                return $a['mes'] <=> $b['mes'];
            }
            return $a['anio'] <=> $b['anio'];
        });

        $insertadas = 0;
        $actualizadas = 0;

        DB::transaction(function () use ($filas, $empresaId, &$insertadas, &$actualizadas) {
            // Obtener el último dato de la BD
            $ultimoDatoDB = DatoVentaHistorico::query()
                ->byEmpresa($empresaId)
                ->orderBy('anio', 'desc')
                ->orderBy('mes', 'desc')
                ->first();

            // Calcular próximo período esperado
            [$anioEsperado, $mesEsperado] = $this->calcularProximoPeriodo($ultimoDatoDB);

            foreach ($filas as $fila) {
                // Verificar si el registro ya existe
                $datoExistente = DatoVentaHistorico::query()
                    ->byEmpresa($empresaId)
                    ->where('anio', $fila['anio'])
                    ->where('mes', $fila['mes'])
                    ->first();

                if ($datoExistente) {
                    // ACTUALIZAR: Solo el monto
                    $datoExistente->update(['monto' => $fila['monto']]);
                    $actualizadas++;
                } else {
                    // INSERTAR: Validar continuidad
                    if ($anioEsperado !== null) {
                        // Ya hay datos, validar que sea el siguiente período
                        if ($fila['anio'] != $anioEsperado || $fila['mes'] != $mesEsperado) {
                            $mesNombre = $this->getMesNombre($mesEsperado);
                            throw ValidationException::withMessages([
                                'csv_file' => "Error de continuidad en la fila {$fila['fila_original']}: "
                                    . "se esperaba {$mesNombre} {$anioEsperado}, "
                                    . "pero se encontró {$this->getMesNombre($fila['mes'])} {$fila['anio']}. "
                                    . "No puede haber vacíos en la cadena de datos históricos."
                            ]);
                        }
                    }

                    // Crear el registro
                    DatoVentaHistorico::create([
                        'empresa_id' => $empresaId,
                        'anio' => $fila['anio'],
                        'mes' => $fila['mes'],
                        'monto' => $fila['monto'],
                    ]);

                    $insertadas++;

                    // Recalcular próximo período esperado
                    [$anioEsperado, $mesEsperado] = $this->calcularProximoPeriodo(
                        (object)['anio' => $fila['anio'], 'mes' => $fila['mes']]
                    );
                }
            }
        });

        return [$insertadas, $actualizadas];
    }

    /**
     * Calcular el próximo período esperado.
     *
     * @param object|null $ultimoDato
     * @return array [anio, mes] o [null, null] si no hay datos
     */
    private function calcularProximoPeriodo($ultimoDato): array
    {
        if (!$ultimoDato) {
            return [null, null];
        }

        $mes = $ultimoDato->mes + 1;
        $anio = $ultimoDato->anio;

        if ($mes > 12) {
            $mes = 1;
            $anio++;
        }

        return [$anio, $mes];
    }

    /**
     * Obtener el nombre del mes.
     *
     * @param int $mes
     * @return string
     */
    private function getMesNombre(int $mes): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $meses[$mes] ?? (string)$mes;
    }

    /**
     * Generar contenido de plantilla CSV.
     *
     * @return array
     */
    public function generarPlantillaCSV(): array
    {
        return ['Anio', 'Mes', 'Monto_Venta'];
    }
}
