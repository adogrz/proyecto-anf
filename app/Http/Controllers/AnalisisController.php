<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalisisController extends Controller
{
    public function obtenerComparacionRatios(Empresa $empresa, int $anio)
    {
        // TODO: Implement ratio comparison logic
        return Inertia::render('Analisis/Ratios', [
            'empresa' => $empresa,
            'anio' => $anio,
            // 'ratiosData' => $ratiosData, // Placeholder
        ]);
    }

    public function obtenerAnalisis(Request $request, Empresa $empresa)
    {
        // Verificar que la empresa pertenece al usuario autenticado
        if ($empresa->usuario_id !== auth()->user()->id && !auth()->user()->hasRole('Administrador')) {
            abort(403, 'No tienes permiso para ver esta empresa.');
        }

        // Obtener todos los años disponibles para Balance General
        $aniosBalance = $empresa->estadosFinancieros()
            ->where('tipo_estado', 'balance_general')
            ->distinct()
            ->orderBy('anio', 'asc')
            ->pluck('anio')
            ->toArray();

        // Obtener todos los años disponibles para Estado de Resultados
        $aniosResultados = $empresa->estadosFinancieros()
            ->where('tipo_estado', 'estado_resultados')
            ->distinct()
            ->orderBy('anio', 'asc')
            ->pluck('anio')
            ->toArray();

        // Calcular análisis completo para Balance General
        $analisisBalance = null;
        if (count($aniosBalance) >= 2) {
            $analisisBalance = $this->calcularAnalisisCompleto(
                $empresa, 
                min($aniosBalance), 
                max($aniosBalance), 
                'balance_general'
            );
        }

        // Calcular análisis completo para Estado de Resultados
        $analisisResultados = null;
        if (count($aniosResultados) >= 2) {
            $analisisResultados = $this->calcularAnalisisCompleto(
                $empresa, 
                min($aniosResultados), 
                max($aniosResultados), 
                'estado_resultados'
            );
        }

        return Inertia::render('Analisis/Horizontal-Vertical', [
            'empresa' => $empresa->load('sector'),
            'analisisBalance' => $analisisBalance,
            'analisisResultados' => $analisisResultados,
        ]);
    }

    private function calcularAnalisisCompleto(Empresa $empresa, int $anioInicio, int $anioFin, string $tipoEstado)
    {
        $estadosFinancieros = $empresa->estadosFinancieros()
            ->with(['detalles.catalogoCuenta.cuentaBase'])
            ->where('tipo_estado', $tipoEstado)
            ->whereBetween('anio', [$anioInicio, $anioFin])
            ->orderBy('anio', 'asc')
            ->get();

        if ($estadosFinancieros->isEmpty()) {
            return null;
        }

        $anios = $estadosFinancieros->pluck('anio')->toArray();
        $cuentasAgrupadas = [];

        // Recolectar todas las cuentas
        $codigosFiltro = $tipoEstado === 'balance_general' ? ['1', '2', '3'] : ['4', '5'];
        
        foreach ($estadosFinancieros as $estado) {
            foreach ($estado->detalles as $detalle) {
                $catalogoCuenta = $detalle->catalogoCuenta;
                $cuentaBase = $catalogoCuenta->cuentaBase;
                
                $codigoInicial = substr($cuentaBase->codigo, 0, 1);
                if (!in_array($codigoInicial, $codigosFiltro)) {
                    continue;
                }

                $codigoCuenta = $catalogoCuenta->codigo_cuenta;

                if (!isset($cuentasAgrupadas[$codigoCuenta])) {
                    $cuentasAgrupadas[$codigoCuenta] = [
                        'id' => $catalogoCuenta->id,
                        'codigo' => $codigoCuenta,
                        'nombre' => $catalogoCuenta->nombre_cuenta,
                        'tipo' => $cuentaBase->tipo,
                        'valores' => [],
                        'variaciones_absolutas' => [],
                        'variaciones_porcentuales' => [],
                        'porcentajes_verticales' => [],
                    ];
                }

                $cuentasAgrupadas[$codigoCuenta]['valores'][$estado->anio] = $detalle->valor;
            }
        }

        // Calcular totales para porcentajes verticales
        $codigosConValores = array_keys($cuentasAgrupadas);
        $totalesPorAnio = [];
        
        $codigoBase = $tipoEstado === 'balance_general' ? '1' : '4';
        
        foreach ($estadosFinancieros as $estado) {
            $total = 0;
            
            foreach ($cuentasAgrupadas as $codigo => $cuenta) {
                if (substr($codigo, 0, 1) !== $codigoBase) continue;
                if (!isset($cuenta['valores'][$estado->anio])) continue;
                
                // Verificar si es hoja
                $esHoja = true;
                foreach ($codigosConValores as $otroCodigo) {
                    if ($otroCodigo !== $codigo && strpos($otroCodigo, $codigo . '.') === 0) {
                        $esHoja = false;
                        break;
                    }
                }
                
                if ($esHoja) {
                    $total += $cuenta['valores'][$estado->anio];
                }
            }
            
            $totalesPorAnio[$estado->anio] = $total;
        }

        // Calcular variaciones horizontales y porcentajes verticales
        foreach ($cuentasAgrupadas as $codigo => &$cuenta) {
            // Variaciones horizontales
            for ($i = 1; $i < count($anios); $i++) {
                $anioActual = $anios[$i];
                $anioAnterior = $anios[$i - 1];

                $valorActual = $cuenta['valores'][$anioActual] ?? 0;
                $valorAnterior = $cuenta['valores'][$anioAnterior] ?? 0;

                $variacionAbsoluta = $valorActual - $valorAnterior;
                $variacionPorcentual = $valorAnterior != 0 ? (($variacionAbsoluta / abs($valorAnterior)) * 100) : 0;

                $cuenta['variaciones_absolutas'][$anioActual] = $variacionAbsoluta;
                $cuenta['variaciones_porcentuales'][$anioActual] = $variacionPorcentual;
            }
            
            // Porcentajes verticales
            foreach ($anios as $anio) {
                $valor = $cuenta['valores'][$anio] ?? 0;
                $total = $totalesPorAnio[$anio] ?? 1;
                
                $porcentaje = $total > 0 ? (($valor / $total) * 100) : 0;
                $cuenta['porcentajes_verticales'][$anio] = $porcentaje;
            }
        }

        // Ordenar y estructurar
        $cuentasOrdenadas = $this->ordenarCuentasPorCodigo($cuentasAgrupadas);
        $tipoAnalisis = $tipoEstado === 'balance_general' ? 'balance' : 'resultados';
        $cuentasConTotales = $this->agregarTotalesYHeadersCompleto($cuentasOrdenadas, $tipoAnalisis, $totalesPorAnio);

        return [
            'anios' => $anios,
            'cuentas' => $cuentasConTotales,
        ];
    }

    private function agregarTotalesYHeadersCompleto(array $cuentas, string $tipoAnalisis, array $totalesPorAnio)
    {
        $resultado = [];
        $codigosConValores = array_keys($cuentas);
        
        // Filtrar solo cuentas "hoja" (que no tienen subcuentas)
        $cuentasHoja = [];
        foreach ($cuentas as $codigo => $cuenta) {
            $esHoja = true;
            foreach ($codigosConValores as $otroCodigo) {
                if ($otroCodigo !== $codigo && strpos($otroCodigo, $codigo . '.') === 0) {
                    $esHoja = false;
                    break;
                }
            }
            
            // Solo incluir cuentas hoja (que tienen valores reales)
            if ($esHoja && !empty($cuenta['valores'])) {
                $cuentasHoja[$codigo] = $cuenta;
            }
        }
        
        // Agrupar cuentas hoja por código de nivel 1 (primer dígito del código)
        $seccionesPorCodigo = [];
        foreach ($cuentasHoja as $codigo => $cuenta) {
            // Limpiar el código de posibles espacios
            $codigoLimpio = trim($codigo);
            // Extraer solo el primer carácter numérico
            preg_match('/^(\d)/', $codigoLimpio, $matches);
            $nivel1 = $matches[1] ?? 'X'; // Si no encuentra dígito, usar 'X' como marcador
            
            if (!isset($seccionesPorCodigo[$nivel1])) {
                $seccionesPorCodigo[$nivel1] = [];
            }
            $seccionesPorCodigo[$nivel1][] = $cuenta;
        }

        // Ordenar las secciones por código (1, 2, 3 para balance o 4, 5 para resultados)
        ksort($seccionesPorCodigo);

        // Crear headers y totales para cada sección (solo las que tienen dígito válido)
        foreach ($seccionesPorCodigo as $codigoNivel1 => $cuentasSeccion) {
            if ($codigoNivel1 !== 'X' && !empty($cuentasSeccion)) { // Ignorar cuentas sin código válido o vacías
                $resultado[] = $this->crearHeaderYTotal($codigoNivel1, $cuentasSeccion, $tipoAnalisis, array_keys($cuentasHoja), $totalesPorAnio);
            }
        }

        return $resultado;
    }

    private function crearHeaderYTotal(string $codigoNivel1, array $cuentasSeccion, string $tipoAnalisis, array $codigosConValores, array $totalesPorAnio)
    {
        $nombreHeader = $this->obtenerNombreHeader($codigoNivel1, $tipoAnalisis);

        $header = [
            'codigo' => $codigoNivel1,
            'nombre' => $nombreHeader,
            'tipo' => 'HEADER',
            'valores' => [],
            'variaciones_absolutas' => [],
            'variaciones_porcentuales' => [],
            'porcentajes_verticales' => [],
        ];

        // Calcular totales
        $totalesSeccion = [];
        $variacionesAbsolutas = [];
        $variacionesPorcentuales = [];
        $porcentajesVerticales = [];
        
        $anios = array_keys($cuentasSeccion[0]['valores'] ?? []);

        foreach ($anios as $anio) {
            $totalSeccion = 0;

            foreach ($cuentasSeccion as $cuenta) {
                $codigo = $cuenta['codigo'];
                $esHoja = true;

                foreach ($codigosConValores as $otroCodigo) {
                    if ($otroCodigo !== $codigo && strpos($otroCodigo, $codigo . '.') === 0) {
                        $esHoja = false;
                        break;
                    }
                }

                if ($esHoja && isset($cuenta['valores'][$anio])) {
                    $totalSeccion += $cuenta['valores'][$anio];
                }
            }

            $totalesSeccion[$anio] = $totalSeccion;
        }

        // Calcular variaciones del total
        $aniosOrdenados = array_values($anios);
        for ($i = 1; $i < count($aniosOrdenados); $i++) {
            $anioActual = $aniosOrdenados[$i];
            $anioAnterior = $aniosOrdenados[$i - 1];

            $valorActual = $totalesSeccion[$anioActual] ?? 0;
            $valorAnterior = $totalesSeccion[$anioAnterior] ?? 0;

            $varAbs = $valorActual - $valorAnterior;
            $varPorc = $valorAnterior != 0 ? (($varAbs / abs($valorAnterior)) * 100) : 0;

            $variacionesAbsolutas[$anioActual] = $varAbs;
            $variacionesPorcentuales[$anioActual] = $varPorc;
        }

        // Calcular porcentajes verticales del total
        foreach ($anios as $anio) {
            $valor = $totalesSeccion[$anio] ?? 0;
            $total = $totalesPorAnio[$anio] ?? 1;
            
            $porcentaje = $total > 0 ? (($valor / $total) * 100) : 0;
            $porcentajesVerticales[$anio] = $porcentaje;
        }

        $nombreTotal = $this->obtenerNombreTotal($codigoNivel1, $tipoAnalisis);

        $total = [
            'codigo' => $codigoNivel1,
            'nombre' => $nombreTotal,
            'tipo' => 'TOTAL',
            'valores' => $totalesSeccion,
            'variaciones_absolutas' => $variacionesAbsolutas,
            'variaciones_porcentuales' => $variacionesPorcentuales,
            'porcentajes_verticales' => $porcentajesVerticales,
        ];

        return [
            'header' => $header,
            'cuentas' => $cuentasSeccion,
            'total' => $total,
        ];
    }

    private function agregarTotalesYHeaders(array $cuentas, string $tipo): array
    {
        $resultado = [];
        $totalesPorSeccion = [];
        $seccionActual = null;

        // Identificar qué cuentas son "hojas" (tienen valores pero sus hijos no)
        $codigosConValores = [];
        foreach ($cuentas as $cuenta) {
            if (!empty($cuenta['valores'])) {
                $codigosConValores[] = $cuenta['codigo'];
            }
        }

        // Calcular el gran total de activos por año (para porcentajes verticales)
        $granTotalPorAnio = [];
        foreach ($cuentas as $c) {
            if (substr($c['codigo'], 0, 1) === '1' && !empty($c['valores'])) {
                // Verificar si es hoja
                $esHojaGranTotal = true;
                foreach ($codigosConValores as $codigo) {
                    if ($codigo !== $c['codigo'] && strpos($codigo, $c['codigo'] . '.') === 0) {
                        $esHojaGranTotal = false;
                        break;
                    }
                }

                if ($esHojaGranTotal) {
                    foreach ($c['valores'] as $anio => $valor) {
                        if (!isset($granTotalPorAnio[$anio])) {
                            $granTotalPorAnio[$anio] = 0;
                        }
                        $granTotalPorAnio[$anio] += $valor;
                    }
                }
            }
        }

        foreach ($cuentas as $cuenta) {
            $codigoPrincipal = substr($cuenta['codigo'], 0, 1);

            // Detectar cambio de sección principal
            if ($codigoPrincipal !== $seccionActual) {
                // Calcular variaciones y porcentajes ANTES de agregar el total
                if ($seccionActual !== null && isset($totalesPorSeccion[$seccionActual])) {
                    // Calcular variaciones del total
                    if (!empty($totalesPorSeccion[$seccionActual]['valores'])) {
                        $anios = array_keys($totalesPorSeccion[$seccionActual]['valores']);
                        sort($anios);

                        // Variaciones horizontales
                        for ($i = 1; $i < count($anios); $i++) {
                            $anioActual = $anios[$i];
                            $anioAnterior = $anios[$i - 1];

                            $valorActual = $totalesPorSeccion[$seccionActual]['valores'][$anioActual] ?? 0;
                            $valorAnterior = $totalesPorSeccion[$seccionActual]['valores'][$anioAnterior] ?? 0;

                            $variacionAbsoluta = $valorActual - $valorAnterior;
                            $variacionPorcentual = $valorAnterior != 0 ? (($variacionAbsoluta / abs($valorAnterior)) * 100) : 0;

                            $totalesPorSeccion[$seccionActual]['variaciones_absolutas'][$anioActual] = $variacionAbsoluta;
                            $totalesPorSeccion[$seccionActual]['variaciones_porcentuales'][$anioActual] = $variacionPorcentual;
                        }

                        // Porcentajes verticales del total
                        foreach ($anios as $anio) {
                            $valorTotal = $totalesPorSeccion[$seccionActual]['valores'][$anio] ?? 0;
                            $granTotal = $granTotalPorAnio[$anio] ?? 1;

                            if ($granTotal > 0) {
                                $porcentaje = ($valorTotal / $granTotal) * 100;
                            } else {
                                $porcentaje = 0;
                            }

                            $totalesPorSeccion[$seccionActual]['porcentajes_verticales'][$anio] = $porcentaje;
                        }
                    }

                    $resultado[] = $totalesPorSeccion[$seccionActual];
                }

                // Agregar header de nueva sección
                $seccionActual = $codigoPrincipal;
                $nombreHeader = $this->obtenerNombreHeader($codigoPrincipal, $tipo);

                $resultado[] = [
                    'id' => 'header_' . $codigoPrincipal,
                    'codigo' => '',
                    'nombre' => $nombreHeader,
                    'tipo' => 'HEADER',
                    'es_header' => true,
                    'valores' => [],
                    'variaciones_absolutas' => [],
                    'variaciones_porcentuales' => [],
                    'porcentajes_verticales' => [],
                ];

                // Inicializar total de sección
                $totalesPorSeccion[$codigoPrincipal] = [
                    'id' => 'total_' . $codigoPrincipal,
                    'codigo' => '',
                    'nombre' => $this->obtenerNombreTotal($codigoPrincipal, $tipo),
                    'tipo' => 'TOTAL',
                    'es_total' => true,
                    'valores' => [],
                    'variaciones_absolutas' => [],
                    'variaciones_porcentuales' => [],
                    'porcentajes_verticales' => [],
                ];
            }

            // Agregar cuenta
            $resultado[] = $cuenta;

            // Verificar si esta cuenta es una "hoja"
            $esHoja = true;
            if (!empty($cuenta['valores'])) {
                foreach ($codigosConValores as $codigo) {
                    if ($codigo !== $cuenta['codigo'] && strpos($codigo, $cuenta['codigo'] . '.') === 0) {
                        $esHoja = false;
                        break;
                    }
                }
            } else {
                $esHoja = false;
            }

            // Solo sumar las cuentas "hoja"
            if ($esHoja) {
                foreach ($cuenta['valores'] as $anio => $valor) {
                    if (!isset($totalesPorSeccion[$codigoPrincipal]['valores'][$anio])) {
                        $totalesPorSeccion[$codigoPrincipal]['valores'][$anio] = 0;
                    }
                    $totalesPorSeccion[$codigoPrincipal]['valores'][$anio] += $valor;
                }
            }
        }

        // Agregar ÚLTIMO total y calcular sus variaciones y porcentajes
        if ($seccionActual !== null && isset($totalesPorSeccion[$seccionActual])) {
            // Calcular variaciones y porcentajes del total
            if (!empty($totalesPorSeccion[$seccionActual]['valores'])) {
                $anios = array_keys($totalesPorSeccion[$seccionActual]['valores']);
                sort($anios);

                // Variaciones horizontales
                for ($i = 1; $i < count($anios); $i++) {
                    $anioActual = $anios[$i];
                    $anioAnterior = $anios[$i - 1];

                    $valorActual = $totalesPorSeccion[$seccionActual]['valores'][$anioActual] ?? 0;
                    $valorAnterior = $totalesPorSeccion[$seccionActual]['valores'][$anioAnterior] ?? 0;

                    $variacionAbsoluta = $valorActual - $valorAnterior;
                    $variacionPorcentual = $valorAnterior != 0 ? (($variacionAbsoluta / abs($valorAnterior)) * 100) : 0;

                    $totalesPorSeccion[$seccionActual]['variaciones_absolutas'][$anioActual] = $variacionAbsoluta;
                    $totalesPorSeccion[$seccionActual]['variaciones_porcentuales'][$anioActual] = $variacionPorcentual;
                }

                // Porcentajes verticales del total
                foreach ($anios as $anio) {
                    $valorTotal = $totalesPorSeccion[$seccionActual]['valores'][$anio] ?? 0;
                    $granTotal = $granTotalPorAnio[$anio] ?? 1;

                    if ($granTotal > 0) {
                        $porcentaje = ($valorTotal / $granTotal) * 100;
                    } else {
                        $porcentaje = 0;
                    }

                    $totalesPorSeccion[$seccionActual]['porcentajes_verticales'][$anio] = $porcentaje;
                }
            }

            $resultado[] = $totalesPorSeccion[$seccionActual];
        }

        return $resultado;
    }

    private function agregarTotalesYHeadersVertical(array $cuentas, array $totalesIngresos, array $anios): array
    {
        $resultado = [];
        $totalesPorSeccion = [];
        $seccionActual = null;

        // Identificar cuentas "hoja"
        $codigosConValores = [];
        foreach ($cuentas as $cuenta) {
            if (!empty($cuenta['valores'])) {
                $codigosConValores[] = $cuenta['codigo'];
            }
        }

        foreach ($cuentas as $cuenta) {
            $codigoPrincipal = substr($cuenta['codigo'], 0, 1);

            // Detectar cambio de sección principal
            if ($codigoPrincipal !== $seccionActual) {
                // CAMBIO: Calcular porcentajes ANTES de agregar el total
                if ($seccionActual !== null && isset($totalesPorSeccion[$seccionActual])) {
                    // Calcular porcentajes verticales del total de la sección anterior
                    foreach ($anios as $anio) {
                        $valorTotal = $totalesPorSeccion[$seccionActual]['valores'][$anio] ?? 0;
                        $totalIngresos = $totalesIngresos[$anio] ?? 1;

                        if ($totalIngresos > 0) {
                            $porcentaje = ($valorTotal / $totalIngresos) * 100;
                        } else {
                            $porcentaje = 0;
                        }

                        $totalesPorSeccion[$seccionActual]['porcentajes_verticales'][$anio] = $porcentaje;
                    }

                    $resultado[] = $totalesPorSeccion[$seccionActual];
                }

                // Agregar header de nueva sección
                $seccionActual = $codigoPrincipal;
                $nombreHeader = $this->obtenerNombreHeader($codigoPrincipal, 'resultados');

                $resultado[] = [
                    'id' => 'header_' . $codigoPrincipal,
                    'codigo' => '',
                    'nombre' => $nombreHeader,
                    'tipo' => 'HEADER',
                    'es_header' => true,
                    'valores' => [],
                    'variaciones_absolutas' => [],
                    'variaciones_porcentuales' => [],
                    'porcentajes_verticales' => [],
                ];

                // Inicializar total de sección
                $totalesPorSeccion[$codigoPrincipal] = [
                    'id' => 'total_' . $codigoPrincipal,
                    'codigo' => '',
                    'nombre' => $this->obtenerNombreTotal($codigoPrincipal, 'resultados'),
                    'tipo' => 'TOTAL',
                    'es_total' => true,
                    'valores' => [],
                    'variaciones_absolutas' => [],
                    'variaciones_porcentuales' => [],
                    'porcentajes_verticales' => [],
                ];
            }

            // Agregar cuenta
            $resultado[] = $cuenta;

            // Verificar si es hoja
            $esHoja = true;
            if (!empty($cuenta['valores'])) {
                foreach ($codigosConValores as $codigo) {
                    if ($codigo !== $cuenta['codigo'] && strpos($codigo, $cuenta['codigo'] . '.') === 0) {
                        $esHoja = false;
                        break;
                    }
                }
            } else {
                $esHoja = false;
            }

            // Solo sumar las cuentas "hoja"
            if ($esHoja) {
                foreach ($cuenta['valores'] as $anio => $valor) {
                    if (!isset($totalesPorSeccion[$codigoPrincipal]['valores'][$anio])) {
                        $totalesPorSeccion[$codigoPrincipal]['valores'][$anio] = 0;
                    }
                    $totalesPorSeccion[$codigoPrincipal]['valores'][$anio] += $valor;
                }
            }
        }

        // Agregar ÚLTIMO total y calcular porcentajes
        if ($seccionActual !== null && isset($totalesPorSeccion[$seccionActual])) {
            // Calcular porcentajes verticales del total de la última sección
            foreach ($anios as $anio) {
                $valorTotal = $totalesPorSeccion[$seccionActual]['valores'][$anio] ?? 0;
                $totalIngresos = $totalesIngresos[$anio] ?? 1;

                if ($totalIngresos > 0) {
                    $porcentaje = ($valorTotal / $totalIngresos) * 100;
                } else {
                    $porcentaje = 0;
                }

                $totalesPorSeccion[$seccionActual]['porcentajes_verticales'][$anio] = $porcentaje;
            }

            $resultado[] = $totalesPorSeccion[$seccionActual];
        }

        return $resultado;
    }

    private function calcularAnalisisHorizontal(Empresa $empresa, int $anioInicio, int $anioFin)
    {
        // Obtener SOLO estados financieros de BALANCE GENERAL
        $estadosFinancieros = $empresa->estadosFinancieros()
            ->with(['detalles.catalogoCuenta.cuentaBase'])
            ->where('tipo_estado', 'balance_general')
            ->whereBetween('anio', [$anioInicio, $anioFin])
            ->orderBy('anio', 'asc')
            ->get();

        if ($estadosFinancieros->isEmpty()) {
            return null;
        }

        $anios = $estadosFinancieros->pluck('anio')->toArray();
        $cuentasAgrupadas = [];

        // Primero: recolectar todas las cuentas
        foreach ($estadosFinancieros as $estado) {
            foreach ($estado->detalles as $detalle) {
                $catalogoCuenta = $detalle->catalogoCuenta;
                $cuentaBase = $catalogoCuenta->cuentaBase;

                $codigoInicial = substr($cuentaBase->codigo, 0, 1);
                if (!in_array($codigoInicial, ['1', '2', '3'])) {
                    continue;
                }

                $codigoCuenta = $catalogoCuenta->codigo_cuenta;

                if (!isset($cuentasAgrupadas[$codigoCuenta])) {
                    $cuentasAgrupadas[$codigoCuenta] = [
                        'id' => $catalogoCuenta->id,
                        'codigo' => $codigoCuenta,
                        'nombre' => $catalogoCuenta->nombre_cuenta,
                        'tipo' => $cuentaBase->tipo,
                        'valores' => [],
                        'variaciones_absolutas' => [],
                        'variaciones_porcentuales' => [],
                        'porcentajes_verticales' => [],
                    ];
                }

                $cuentasAgrupadas[$codigoCuenta]['valores'][$estado->anio] = $detalle->valor;
            }
        }

        // Segundo: identificar cuentas "hoja" (que tienen valores pero NO tienen subcuentas)
        $codigosConValores = array_keys($cuentasAgrupadas);
        $totalesActivosPorAnio = [];

        foreach ($estadosFinancieros as $estado) {
            $totalActivos = 0;

            foreach ($cuentasAgrupadas as $codigo => $cuenta) {
                // Solo procesar activos en este año
                if (substr($codigo, 0, 1) !== '1') continue;
                if (!isset($cuenta['valores'][$estado->anio])) continue;

                // Verificar si es hoja
                $esHoja = true;
                foreach ($codigosConValores as $otroCodigo) {
                    if ($otroCodigo !== $codigo && strpos($otroCodigo, $codigo . '.') === 0) {
                        $esHoja = false;
                        break;
                    }
                }

                if ($esHoja) {
                    $totalActivos += $cuenta['valores'][$estado->anio];
                }
            }

            $totalesActivosPorAnio[$estado->anio] = $totalActivos;
        }

        // Calcular variaciones
        foreach ($cuentasAgrupadas as $codigo => &$cuenta) {
            for ($i = 1; $i < count($anios); $i++) {
                $anioActual = $anios[$i];
                $anioAnterior = $anios[$i - 1];

                $valorActual = $cuenta['valores'][$anioActual] ?? 0;
                $valorAnterior = $cuenta['valores'][$anioAnterior] ?? 0;

                $variacionAbsoluta = $valorActual - $valorAnterior;
                $variacionPorcentual = $valorAnterior != 0 ? (($variacionAbsoluta / abs($valorAnterior)) * 100) : 0;

                $cuenta['variaciones_absolutas'][$anioActual] = $variacionAbsoluta;
                $cuenta['variaciones_porcentuales'][$anioActual] = $variacionPorcentual;
            }

            // Calcular porcentajes verticales para cada año
            foreach ($anios as $anio) {
                $valor = $cuenta['valores'][$anio] ?? 0;
                $totalActivos = $totalesActivosPorAnio[$anio] ?? 1;

                if ($totalActivos > 0) {
                    $porcentaje = ($valor / $totalActivos) * 100;
                } else {
                    $porcentaje = 0;
                }

                $cuenta['porcentajes_verticales'][$anio] = $porcentaje;
            }
        }

        // Ordenar y estructurar
        $cuentasOrdenadas = $this->ordenarCuentasPorCodigo($cuentasAgrupadas);
        $cuentasConTotales = $this->agregarTotalesYHeaders($cuentasOrdenadas, 'balance');

        return [
            'anios' => $anios,
            'cuentas' => $cuentasConTotales,
        ];
    }

    private function calcularAnalisisVertical(Empresa $empresa, int $anioInicio, int $anioFin)
    {
        // Obtener SOLO estados financieros de ESTADO DE RESULTADOS
        $estadosFinancieros = $empresa->estadosFinancieros()
            ->with(['detalles.catalogoCuenta.cuentaBase'])
            ->where('tipo_estado', 'estado_resultados')
            ->whereBetween('anio', [$anioInicio, $anioFin])
            ->orderBy('anio', 'asc')
            ->get();

        if ($estadosFinancieros->isEmpty()) {
            return null;
        }

        $anios = $estadosFinancieros->pluck('anio')->toArray();
        $cuentasAgrupadas = [];

        // Primero: recolectar todas las cuentas
        foreach ($estadosFinancieros as $estado) {
            foreach ($estado->detalles as $detalle) {
                $catalogoCuenta = $detalle->catalogoCuenta;
                $cuentaBase = $catalogoCuenta->cuentaBase;

                // FILTRO CRÍTICO: Solo cuentas que pertenecen al Estado de Resultados (códigos 4 y 5)
                $codigoInicial = substr($cuentaBase->codigo, 0, 1);
                if (!in_array($codigoInicial, ['4', '5'])) {
                    continue; // Saltar cuentas de activos (1), pasivos (2) y patrimonio (3)
                }

                $codigoCuenta = $catalogoCuenta->codigo_cuenta;

                if (!isset($cuentasAgrupadas[$codigoCuenta])) {
                    $cuentasAgrupadas[$codigoCuenta] = [
                        'id' => $catalogoCuenta->id,
                        'codigo' => $codigoCuenta,
                        'nombre' => $catalogoCuenta->nombre_cuenta,
                        'tipo' => $cuentaBase->tipo,
                        'valores' => [],
                        'variaciones_absolutas' => [],
                        'variaciones_porcentuales' => [],
                        'porcentajes_verticales' => [],
                    ];
                }

                $cuentasAgrupadas[$codigoCuenta]['valores'][$estado->anio] = $detalle->valor;
            }
        }

        // Segundo: calcular totales de ingresos usando detección de hojas
        $codigosConValores = array_keys($cuentasAgrupadas);
        $totalesIngresosPorAnio = [];

        foreach ($estadosFinancieros as $estado) {
            $totalIngresos = 0;

            foreach ($cuentasAgrupadas as $codigo => $cuenta) {
                // Solo procesar ingresos (cuentas 4) en este año
                if (substr($codigo, 0, 1) !== '4') continue;
                if (!isset($cuenta['valores'][$estado->anio])) continue;

                // Verificar si es hoja (no tiene subcuentas)
                $esHoja = true;
                foreach ($codigosConValores as $otroCodigo) {
                    if ($otroCodigo !== $codigo && strpos($otroCodigo, $codigo . '.') === 0) {
                        $esHoja = false;
                        break;
                    }
                }

                if ($esHoja) {
                    $totalIngresos += $cuenta['valores'][$estado->anio];
                }
            }

            $totalesIngresosPorAnio[$estado->anio] = $totalIngresos;
        }

        // Calcular porcentajes verticales ANTES de ordenar
        foreach ($cuentasAgrupadas as $codigo => &$cuenta) {
            foreach ($anios as $anio) {
                $valor = $cuenta['valores'][$anio] ?? 0;
                $totalIngresos = $totalesIngresosPorAnio[$anio] ?? 1;

                $porcentaje = $totalIngresos != 0 ? (($valor / $totalIngresos) * 100) : 0;
                $cuenta['porcentajes_verticales'][$anio] = $porcentaje;
            }
        }

        // Ordenar y estructurar
        $cuentasOrdenadas = $this->ordenarCuentasPorCodigo($cuentasAgrupadas);
        $cuentasConTotales = $this->agregarTotalesYHeadersVertical($cuentasOrdenadas, $totalesIngresosPorAnio, $anios);

        return [
            'anios' => $anios,
            'cuentas' => $cuentasConTotales,
        ];
    }

    private function ordenarCuentasPorCodigo(array $cuentas): array
    {
        // Eliminar duplicados basándose en el código de cuenta
        $cuentasUnicas = [];
        $codigosVistos = [];

        foreach ($cuentas as $cuenta) {
            $codigo = $cuenta['codigo'];

            if (!in_array($codigo, $codigosVistos)) {
                $cuentasUnicas[$codigo] = $cuenta; // MANTENER el código como key
                $codigosVistos[] = $codigo;
            }
        }

        // Ordenar las cuentas únicas por código
        uksort($cuentasUnicas, function ($a, $b) {
            return version_compare($a, $b);
        });

        return $cuentasUnicas;
    }

    private function obtenerNombreHeader(string $codigo, string $tipo): string
    {
        if ($tipo === 'balance') {
            $nombres = [
                '1' => 'ACTIVOS',
                '2' => 'PASIVOS',
                '3' => 'PATRIMONIO',
            ];
        } else {
            $nombres = [
                '4' => 'INGRESOS',
                '5' => 'GASTOS',
            ];
        }

        return $nombres[$codigo] ?? 'OTROS';
    }

    private function obtenerNombreTotal(string $codigo, string $tipo): string
    {
        if ($tipo === 'balance') {
            $nombres = [
                '1' => 'TOTAL ACTIVOS',
                '2' => 'TOTAL PASIVOS',
                '3' => 'TOTAL PATRIMONIO',
            ];
        } else {
            $nombres = [
                '4' => 'TOTAL INGRESOS',
                '5' => 'TOTAL GASTOS',
            ];
        }

        return $nombres[$codigo] ?? 'TOTAL OTROS';
    }


    public function obtenerHistorialCuenta(Request $request, Empresa $empresa)
    {
        $request->validate([
            'cuenta_id' => 'required|exists:cuentas_base,id',
        ]);
        // TODO: Implement account history logic
        return Inertia::render('Analisis/HistorialCuenta', [
            'empresa' => $empresa,
            'cuentaId' => $request->input('cuenta_id'),
            // 'historyData' => $historyData, // Placeholder
        ]);
    }
}