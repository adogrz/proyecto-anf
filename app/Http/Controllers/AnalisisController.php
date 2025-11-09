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
        if ($empresa->usuario_id !== auth()->user()->id) {
            abort(403, 'No tienes permiso para ver esta empresa.');
        }

        $aniosDisponibles = [];
        $analisisData = null;
        $anioInicio = null;
        $anioFin = null;
        $tipoAnalisis = $request->input('tipo_analisis', 'horizontal');

        // Obtener años disponibles según el tipo de análisis
        $tipoEstado = $tipoAnalisis === 'horizontal' ? 'balance_general' : 'estado_resultados';
        
        $aniosDisponibles = $empresa->estadosFinancieros()
            ->where('tipo_estado', $tipoEstado)
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->toArray();

        // Si hay años de inicio y fin
        if ($request->filled('anio_inicio') && $request->filled('anio_fin')) {
            $anioInicio = (int) $request->anio_inicio;
            $anioFin = (int) $request->anio_fin;

            if ($tipoAnalisis === 'horizontal') {
                $analisisData = $this->calcularAnalisisHorizontal($empresa, $anioInicio, $anioFin);
            } else {
                $analisisData = $this->calcularAnalisisVertical($empresa, $anioInicio, $anioFin);
            }
        }

        return Inertia::render('Analisis/Horizontal-Vertical', [
            'empresa' => $empresa->load('sector'),
            'aniosDisponibles' => $aniosDisponibles,
            'anioInicio' => $anioInicio,
            'anioFin' => $anioFin,
            'analisisData' => $analisisData,
            'tipoAnalisis' => $tipoAnalisis,
        ]);
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
                $cuentasUnicas[] = $cuenta;
                $codigosVistos[] = $codigo;
            }
        }

        // Ordenar las cuentas únicas
        usort($cuentasUnicas, function ($a, $b) {
            return version_compare($a['codigo'], $b['codigo']);
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