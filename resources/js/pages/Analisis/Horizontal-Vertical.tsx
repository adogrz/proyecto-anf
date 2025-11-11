import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { TrendingUp, TrendingDown, Minus, ChevronDown } from 'lucide-react';
import { useState, Fragment } from 'react';

interface Cuenta {
    id: number | string;
    codigo: string;
    nombre: string;
    tipo: 'DETALLE' | 'AGRUPACION' | 'TOTAL' | 'HEADER';
    valores: Record<number, number>;
    variaciones_absolutas: Record<number, number>;
    variaciones_porcentuales: Record<number, number>;
    porcentajes_verticales: Record<number, number>;
}

interface Seccion {
    header: Cuenta;
    cuentas: Cuenta[];
    total: Cuenta;
}

interface AnalisisTipo {
    anios: number[];
    cuentas: Seccion[];
}

interface EmpresaCompleta {
    id: number;
    nombre: string;
    sector: {
        nombre: string;
    };
}

interface Props {
    empresa: EmpresaCompleta;
    analisisBalance: AnalisisTipo | null;
    analisisResultados: AnalisisTipo | null;
}

export default function Analisis({ 
    empresa,
    analisisBalance,
    analisisResultados
}: Props) {

    const [tipoVisualizacion, setTipoVisualizacion] = useState<'horizontal' | 'vertical'>('horizontal');
    
    // Obtener todos los años disponibles
    const todosLosAnios = analisisBalance?.anios || analisisResultados?.anios || [];
    
    // Estado para controlar la visibilidad de cada año
    const [columnasVisibles, setColumnasVisibles] = useState<Record<number, boolean>>(
        todosLosAnios.reduce((acc, anio) => ({ ...acc, [anio]: true }), {})
    );

    const toggleColumna = (anio: number) => {
        setColumnasVisibles(prev => ({
            ...prev,
            [anio]: !prev[anio]
        }));
    };

    const getNivelCuenta = (codigo: string): number => {
        if (!codigo) return 0;
        const segmentos = codigo.split('.');
        return segmentos.length;
    };

    const getPaddingPorNivel = (codigo: string): string => {
        if (!codigo) return '0.5rem';
        const nivel = getNivelCuenta(codigo);
        const paddingBase = 0.5;
        const paddingIncremento = 1; 
        
        return `${paddingBase + (nivel - 1) * paddingIncremento}rem`;
    };

    const formatearMoneda = (valor: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
        }).format(valor);
    };

    const formatearPorcentaje = (valor: number) => {
        const signo = valor > 0 ? '+' : '';
        return `${signo}${valor.toFixed(2)}%`;
    };

    const getVariacionIcon = (valor: number) => {
        if (valor > 0) return <TrendingUp className="h-4 w-4 text-green-600" />;
        if (valor < 0) return <TrendingDown className="h-4 w-4 text-red-600" />;
        return <Minus className="h-4 w-4 text-gray-400" />;
    };

    const getVariacionColor = (valor: number) => {
        if (valor > 0) return 'text-green-600';
        if (valor < 0) return 'text-red-600';
        return 'text-gray-600';
    };

    const getFontWeightPorNivel = (codigo: string): string => {
        if (!codigo) return 'font-bold';
        const nivel = getNivelCuenta(codigo);
        if (nivel === 1) return 'font-bold';
        if (nivel === 2) return 'font-semibold';
        return 'font-normal';
    };

    const renderFilaCuenta = (cuenta: Cuenta) => {
        const padding = getPaddingPorNivel(cuenta.codigo);
        const fontWeight = getFontWeightPorNivel(cuenta.codigo);

        return (
            <TableCell 
                className={`sticky left-0 bg-card border-r ${fontWeight}`}
                style={{ paddingLeft: padding }}
            >
                <div className="flex items-center gap-2">
                    <span className="text-xs text-gray-500 min-w-[80px]">{cuenta.codigo}</span>
                    <span>{cuenta.nombre}</span>
                </div>
            </TableCell>
        );
    };

    const renderTablaAnalisis = (analisis: AnalisisTipo | null, titulo: string, descripcion: string) => {
        if (!analisis || analisis.cuentas.length === 0) {
            return (
                <Card>
                    <CardHeader>
                        <CardTitle>{titulo}</CardTitle>
                        <CardDescription>{descripcion}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p className="text-center text-gray-500 py-8">
                            No hay datos disponibles para este estado financiero.
                        </p>
                    </CardContent>
                </Card>
            );
        }

        const { anios, cuentas } = analisis;
        
        // Filtrar solo los años visibles
        const aniosVisibles = anios.filter(anio => columnasVisibles[anio]);

        return (
            <Card>
                <CardHeader>
                    <CardTitle>{titulo}</CardTitle>
                    <CardDescription>{descripcion}</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {/* Selector de columnas (solo para horizontal) */}
                    {tipoVisualizacion === 'horizontal' && (
                        <div className="flex justify-end">
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        Columnas <ChevronDown className="ml-2 h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-[200px]">
                                    {anios.map((anio) => (
                                        <DropdownMenuCheckboxItem
                                            key={anio}
                                            checked={columnasVisibles[anio]}
                                            onCheckedChange={() => toggleColumna(anio)}
                                        >
                                            {anio}
                                        </DropdownMenuCheckboxItem>
                                    ))}
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    )}
                    
                    <div className="rounded-md border overflow-auto max-h-[70vh]">
                        <Table>
                            <TableHeader className="sticky top-0 bg-muted z-10">
                                <TableRow>
                                    <TableHead className="sticky left-0 bg-muted border-r font-bold min-w-[250px]">
                                        Cuenta
                                    </TableHead>
                                    {aniosVisibles.map((anio, index) => {
                                        const siguienteAnioVisible = aniosVisibles[index + 1];
                                        
                                        // Solo mostrar si hay un siguiente año para comparar
                                        if (!siguienteAnioVisible) return null;
                                        
                                        return (
                                            <Fragment key={`grupo-${anio}-${siguienteAnioVisible}`}>
                                                {/* Primer año del par */}
                                                <TableHead 
                                                    className="text-center bg-muted border-x font-bold"
                                                    colSpan={tipoVisualizacion === 'vertical' ? 2 : 1}
                                                >
                                                    {anio}
                                                </TableHead>
                                                {/* Segundo año del par */}
                                                {tipoVisualizacion === 'horizontal' && (
                                                    <>
                                                        <TableHead 
                                                            className="text-center bg-muted border-x font-bold"
                                                        >
                                                            {siguienteAnioVisible}
                                                        </TableHead>
                                                        <TableHead 
                                                            className="text-center bg-muted border-x font-bold" 
                                                            colSpan={2}
                                                        >
                                                            {anio} vs {siguienteAnioVisible}
                                                        </TableHead>
                                                    </>
                                                )}
                                                {tipoVisualizacion === 'vertical' && (
                                                    <TableHead 
                                                        className="text-center bg-muted border-x font-bold"
                                                        colSpan={2}
                                                    >
                                                        {siguienteAnioVisible}
                                                    </TableHead>
                                                )}
                                            </Fragment>
                                        );
                                    })}
                                </TableRow>
                                <TableRow className="bg-muted/50">
                                    <TableHead className="sticky left-0 bg-muted/50 border-r"></TableHead>
                                    {aniosVisibles.map((anio, index) => {
                                        const siguienteAnioVisible = aniosVisibles[index + 1];
                                        
                                        // Solo mostrar si hay un siguiente año para comparar
                                        if (!siguienteAnioVisible) return null;
                                        
                                        return (
                                            <Fragment key={`sub-grupo-${anio}-${siguienteAnioVisible}`}>
                                                {/* Valor primer año */}
                                                <TableHead className="text-center text-xs bg-muted/50">
                                                    Valor
                                                </TableHead>
                                                {tipoVisualizacion === 'vertical' && (
                                                    <TableHead className="text-center text-xs bg-muted/50">
                                                        %
                                                    </TableHead>
                                                )}
                                                {/* Valor segundo año */}
                                                <TableHead className="text-center text-xs bg-muted/50">
                                                    Valor
                                                </TableHead>
                                                {tipoVisualizacion === 'vertical' && (
                                                    <TableHead className="text-center text-xs bg-muted/50">
                                                        %
                                                    </TableHead>
                                                )}
                                                {/* Variaciones (solo en horizontal) */}
                                                {tipoVisualizacion === 'horizontal' && (
                                                    <>
                                                        <TableHead className="text-center text-xs bg-muted/50">
                                                            Var. Abs.
                                                        </TableHead>
                                                        <TableHead className="text-center text-xs bg-muted/50">
                                                            Var. Rel.
                                                        </TableHead>
                                                    </>
                                                )}
                                            </Fragment>
                                        );
                                    })}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {cuentas.map((seccion, secIndex) => {
                                    // Calcular colspan dinámico basado en años visibles
                                    const calcularColspan = () => {
                                        if (tipoVisualizacion === 'vertical') {
                                            return (aniosVisibles.length * 2) + 1; // Cuenta + años (valor + %)
                                        } else {
                                            // Horizontal: cada par consecutivo = 2 valores + 2 variaciones = 4 columnas
                                            const numPares = aniosVisibles.length - 1; // n-1 comparaciones
                                            return 1 + (numPares * 4); // 1 cuenta + (pares * 4)
                                        }
                                    };
                                    
                                    return (
                                        <>
                                            <TableRow key={`header-${secIndex}`} className="bg-slate-700 hover:bg-slate-700">
                                                <TableCell 
                                                    colSpan={calcularColspan()}
                                                    className="sticky left-0 bg-slate-700 font-bold text-white text-lg py-3"
                                                >
                                                    {seccion.header.nombre}
                                                </TableCell>
                                            </TableRow>

                                            {seccion.cuentas.map((cuenta) => (
                                                <TableRow key={cuenta.id}>
                                                    {renderFilaCuenta(cuenta)}
                                                    {aniosVisibles.map((anio, index) => {
                                                        const siguienteAnioVisible = aniosVisibles[index + 1];
                                                        
                                                        // Solo mostrar si hay un siguiente año para comparar
                                                        if (!siguienteAnioVisible) return null;
                                                        
                                                        return (
                                                            <Fragment key={`data-${anio}-${siguienteAnioVisible}`}>
                                                                {/* Valor del primer año */}
                                                                <TableCell className="text-right">
                                                                    {formatearMoneda(cuenta.valores[anio] || 0)}
                                                                </TableCell>
                                                                {tipoVisualizacion === 'vertical' && (
                                                                    <TableCell className="text-right text-blue-600">
                                                                        {formatearPorcentaje(cuenta.porcentajes_verticales[anio] || 0)}
                                                                    </TableCell>
                                                                )}
                                                                {/* Valor del segundo año */}
                                                                <TableCell className="text-right">
                                                                    {formatearMoneda(cuenta.valores[siguienteAnioVisible] || 0)}
                                                                </TableCell>
                                                                {tipoVisualizacion === 'vertical' && (
                                                                    <TableCell className="text-right text-blue-600">
                                                                        {formatearPorcentaje(cuenta.porcentajes_verticales[siguienteAnioVisible] || 0)}
                                                                    </TableCell>
                                                                )}
                                                                {/* Variaciones (solo en horizontal) */}
                                                                {tipoVisualizacion === 'horizontal' && (
                                                                    <>
                                                                        <TableCell 
                                                                            className={`text-right ${getVariacionColor(cuenta.variaciones_absolutas[siguienteAnioVisible] || 0)}`}
                                                                        >
                                                                            <div className="flex items-center justify-end gap-1">
                                                                                {getVariacionIcon(cuenta.variaciones_absolutas[siguienteAnioVisible] || 0)}
                                                                                <span>{formatearMoneda(Math.abs(cuenta.variaciones_absolutas[siguienteAnioVisible] || 0))}</span>
                                                                            </div>
                                                                        </TableCell>
                                                                        <TableCell 
                                                                            className={`text-right ${getVariacionColor(cuenta.variaciones_porcentuales[siguienteAnioVisible] || 0)}`}
                                                                        >
                                                                            {formatearPorcentaje(cuenta.variaciones_porcentuales[siguienteAnioVisible] || 0)}
                                                                        </TableCell>
                                                                    </>
                                                                )}
                                                            </Fragment>
                                                        );
                                                    })}
                                                </TableRow>
                                            ))}

                                            <TableRow className="bg-muted/30 hover:bg-muted/40 font-semibold">
                                                <TableCell className="sticky left-0 bg-muted/30 border-r">
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-xs text-gray-500 min-w-[80px]">{seccion.total.codigo}</span>
                                                        <span>{seccion.total.nombre}</span>
                                                    </div>
                                                </TableCell>
                                                {aniosVisibles.map((anio, index) => {
                                                    const siguienteAnioVisible = aniosVisibles[index + 1];
                                                    
                                                    // Solo mostrar si hay un siguiente año para comparar
                                                    if (!siguienteAnioVisible) return null;
                                                    
                                                    return (
                                                        <Fragment key={`total-${anio}-${siguienteAnioVisible}`}>
                                                            {/* Valor del primer año */}
                                                            <TableCell className="text-right bg-muted/30">
                                                                {formatearMoneda(seccion.total.valores[anio] || 0)}
                                                            </TableCell>
                                                            {tipoVisualizacion === 'vertical' && (
                                                                <TableCell className="text-right bg-muted/30 text-blue-600">
                                                                    {formatearPorcentaje(seccion.total.porcentajes_verticales[anio] || 0)}
                                                                </TableCell>
                                                            )}
                                                            {/* Valor del segundo año */}
                                                            <TableCell className="text-right bg-muted/30">
                                                                {formatearMoneda(seccion.total.valores[siguienteAnioVisible] || 0)}
                                                            </TableCell>
                                                            {tipoVisualizacion === 'vertical' && (
                                                                <TableCell className="text-right bg-muted/30 text-blue-600">
                                                                    {formatearPorcentaje(seccion.total.porcentajes_verticales[siguienteAnioVisible] || 0)}
                                                                </TableCell>
                                                            )}
                                                            {/* Variaciones (solo en horizontal) */}
                                                            {tipoVisualizacion === 'horizontal' && (
                                                                <>
                                                                    <TableCell 
                                                                        className={`text-right bg-muted/30 ${getVariacionColor(seccion.total.variaciones_absolutas[siguienteAnioVisible] || 0)}`}
                                                                    >
                                                                        <div className="flex items-center justify-end gap-1">
                                                                            {getVariacionIcon(seccion.total.variaciones_absolutas[siguienteAnioVisible] || 0)}
                                                                            <span>{formatearMoneda(Math.abs(seccion.total.variaciones_absolutas[siguienteAnioVisible] || 0))}</span>
                                                                        </div>
                                                                    </TableCell>
                                                                    <TableCell 
                                                                        className={`text-right bg-muted/30 ${getVariacionColor(seccion.total.variaciones_porcentuales[siguienteAnioVisible] || 0)}`}
                                                                    >
                                                                        {formatearPorcentaje(seccion.total.variaciones_porcentuales[siguienteAnioVisible] || 0)}
                                                                    </TableCell>
                                                                </>
                                                            )}
                                                        </Fragment>
                                                    );
                                                })}
                                            </TableRow>
                                        </>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        );
    };

    return (
        <AppLayout>
            <Head title="Análisis Financiero" />

            <div className="py-8">
                <div className="mx-auto max-w-[95%] space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información de la Empresa</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-3 gap-4">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Empresa</p>
                                    <p className="text-lg font-semibold">{empresa.nombre}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Sector</p>
                                    <p className="text-lg font-semibold">{empresa.sector.nombre}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500 mb-2">Tipo de Visualización</p>
                                    <Select value={tipoVisualizacion} onValueChange={(value: 'horizontal' | 'vertical') => setTipoVisualizacion(value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="horizontal">Análisis Horizontal</SelectItem>
                                            <SelectItem value="vertical">Análisis Vertical</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {renderTablaAnalisis(
                        analisisBalance,
                        'Balance General',
                        tipoVisualizacion === 'horizontal'
                            ? 'Análisis Horizontal: Muestra valores por año y variaciones absolutas/relativas entre cada período consecutivo'
                            : 'Análisis Vertical: Muestra valores por año y porcentajes respecto al total de activos'
                    )}

                    {renderTablaAnalisis(
                        analisisResultados,
                        'Estado de Resultados',
                        tipoVisualizacion === 'horizontal'
                            ? 'Análisis Horizontal: Muestra valores por año y variaciones absolutas/relativas entre cada período consecutivo'
                            : 'Análisis Vertical: Muestra valores por año y porcentajes respecto al total de ingresos'
                    )}
                </div>
            </div>
        </AppLayout>
    );
}