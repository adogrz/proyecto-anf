import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { useState, useEffect } from 'react';
import { route } from 'ziggy-js';

interface Cuenta {
    id: number | string;
    codigo: string;
    nombre: string;
    tipo: 'DETALLE' | 'AGRUPACION' | 'TOTAL' | 'HEADER';
    valores: Record<number, number>;
    variaciones_absolutas: Record<number, number>;
    variaciones_porcentuales: Record<number, number>;
    porcentajes_verticales?: Record<number, number>;
    es_total?: boolean;
    es_header?: boolean;
}

interface AnalisisTipo {
    anios: number[];
    cuentas: Cuenta[];
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
    aniosDisponibles: number[];
    anioInicio: number | null;
    anioFin: number | null;
    analisisData: AnalisisTipo | null;
    tipoAnalisis: 'horizontal' | 'vertical';
}

export default function Analisis({ 
    empresa,
    aniosDisponibles,
    anioInicio: anioInicioServer,
    anioFin: anioFinServer,
    analisisData, 
    tipoAnalisis: tipoAnalisisServer
}: Props) {
    const [anioInicio, setAnioInicio] = useState(anioInicioServer?.toString() || '');
    const [anioFin, setAnioFin] = useState(anioFinServer?.toString() || '');
    const [tipoAnalisis, setTipoAnalisis] = useState<'horizontal' | 'vertical'>(tipoAnalisisServer);

    // Sincronizar tipo de análisis del servidor con el estado local
    useEffect(() => {
        setTipoAnalisis(tipoAnalisisServer);
    }, [tipoAnalisisServer]);

    // Sincronizar años del servidor con el estado local
    useEffect(() => {
        setAnioInicio(anioInicioServer?.toString() || '');
        setAnioFin(anioFinServer?.toString() || '');
    }, [anioInicioServer, anioFinServer]);

    // Función para determinar el nivel de la cuenta basado en su código
    const getNivelCuenta = (codigo: string): number => {
        if (!codigo) return 0;
        const segmentos = codigo.split('.');
        return segmentos.length;
    };

    // Función para obtener el padding según el nivel
    const getPaddingPorNivel = (codigo: string): string => {
        if (!codigo) return '0.5rem';
        const nivel = getNivelCuenta(codigo);
        const paddingBase = 0.5;
        const paddingIncremento = 1; 
        
        return `${paddingBase + (nivel - 1) * paddingIncremento}rem`;
    };

    // Funciones de formateo
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

    // Función para obtener el peso de fuente según el nivel
    const getFontWeightPorNivel = (codigo: string): string => {
        if (!codigo) return 'font-bold';
        const nivel = getNivelCuenta(codigo);
        if (nivel === 1) return 'font-bold';
        if (nivel === 2) return 'font-semibold';
        return 'font-normal';
    };

    const handleConsultar = () => {
        if (!anioInicio || !anioFin) return;

        router.get(route('analisis.index', empresa.id), {
            anio_inicio: anioInicio,
            anio_fin: anioFin,
            tipo_analisis: tipoAnalisis,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleTipoAnalisisChange = (value: 'horizontal' | 'vertical') => {
        setTipoAnalisis(value);
        setAnioInicio('');
        setAnioFin('');
        
        router.get(route('analisis.index', empresa.id), {
            tipo_analisis: value,
        }, {
            preserveState: false,
            preserveScroll: false,
        });
    };

    // Renderizar fila según su tipo
    const renderFila = (cuenta: Cuenta) => {
        // FILA DE ENCABEZADO (ACTIVOS, PASIVOS, INGRESOS, etc.)
        if (cuenta.es_header) {
            return (
                <TableRow key={cuenta.id} className="bg-slate-700 hover:bg-slate-700">
                    <TableCell className="sticky left-0 bg-slate-700"></TableCell>
                    <TableCell 
                        colSpan={analisisData!.anios.length * (tipoAnalisis === 'horizontal' ? 3 : 2) + 1}
                        className="sticky left-[100px] bg-slate-700 font-bold text-white text-lg py-3"
                    >
                        {cuenta.nombre}
                    </TableCell>
                </TableRow>
            );
        }

        // FILA DE TOTAL
        if (cuenta.es_total) {

            return (
                <TableRow key={cuenta.id} className="border-t-2 border-b border-border">
                    <TableCell className="font-mono text-sm sticky left-0 font-bold"></TableCell>
                    <TableCell className="sticky left-[100px] font-bold">
                        {cuenta.nombre}
                    </TableCell>
                    
                    {tipoAnalisis === 'horizontal' ? (
                        <>
                            {/* Valores */}
                            {analisisData!.anios.map((anio) => (
                                <TableCell key={anio} className="text-right font-bold">
                                    {formatearMoneda(cuenta.valores[anio] || 0)}
                                </TableCell>
                            ))}
                            {/* Variaciones absolutas */}
                            {analisisData!.anios.slice(1).map((anio) => (
                                <TableCell 
                                    key={`var-${anio}`} 
                                    className={`text-right font-bold ${getVariacionColor(cuenta.variaciones_absolutas[anio] || 0)}`}
                                >
                                    <div className="flex items-center justify-end gap-1">
                                        {getVariacionIcon(cuenta.variaciones_absolutas[anio] || 0)}
                                        <span>{formatearMoneda(cuenta.variaciones_absolutas[anio] || 0)}</span>
                                    </div>
                                </TableCell>
                            ))}
                            {/* Variaciones porcentuales */}
                            {analisisData!.anios.slice(1).map((anio) => (
                                <TableCell 
                                    key={`per-${anio}`} 
                                    className={`text-right font-bold ${getVariacionColor(cuenta.variaciones_porcentuales[anio] || 0)}`}
                                >
                                    {formatearPorcentaje(cuenta.variaciones_porcentuales[anio] || 0)}
                                </TableCell>
                            ))}
                        </>
                    ) : (
                        <>
                            {/* Valores */}
                            {analisisData!.anios.map((anio) => (
                                <TableCell key={`val-${anio}`} className="text-right font-bold">
                                    {formatearMoneda(cuenta.valores[anio] || 0)}
                                </TableCell>
                            ))}
                            {/* Porcentajes verticales */}
                            {analisisData!.anios.map((anio) => (
                                <TableCell key={`pct-${anio}`} className="text-right font-bold">
                                    {formatearPorcentaje(cuenta.porcentajes_verticales?.[anio] || 0)}
                                </TableCell>
                            ))}
                        </>
                    )}
                </TableRow>
            );
        }

        // FILA NORMAL DE CUENTA
        const esAgrupacion = cuenta.tipo === 'AGRUPACION';
        
        return (
            <TableRow 
                key={cuenta.id} 
                className={esAgrupacion ? 'bg-muted/50' : ''}
            >
                <TableCell className="font-mono text-sm sticky left-0">
                    {cuenta.codigo}
                </TableCell>
                <TableCell 
                    className={`sticky left-[100px] ${getFontWeightPorNivel(cuenta.codigo)}`}
                    style={{ paddingLeft: getPaddingPorNivel(cuenta.codigo) }}
                >
                    {cuenta.nombre}
                </TableCell>
                
                {tipoAnalisis === 'horizontal' ? (
                    <>
                        {/* Valores por año */}
                        {analisisData!.anios.map((anio) => (
                            <TableCell key={anio} className="text-right">
                                {formatearMoneda(cuenta.valores[anio] || 0)}
                            </TableCell>
                        ))}
                        
                        {/* Variaciones absolutas */}
                        {analisisData!.anios.slice(1).map((anio) => (
                            <TableCell 
                                key={`var-${anio}`} 
                                className={`text-right ${getVariacionColor(cuenta.variaciones_absolutas[anio] || 0)}`}
                            >
                                <div className="flex items-center justify-end gap-1">
                                    {getVariacionIcon(cuenta.variaciones_absolutas[anio] || 0)}
                                    <span>{formatearMoneda(cuenta.variaciones_absolutas[anio] || 0)}</span>
                                </div>
                            </TableCell>
                        ))}
                        
                        {/* Variaciones porcentuales */}
                        {analisisData!.anios.slice(1).map((anio) => (
                            <TableCell 
                                key={`per-${anio}`} 
                                className={`text-right font-semibold ${getVariacionColor(cuenta.variaciones_porcentuales[anio] || 0)}`}
                            >
                                {formatearPorcentaje(cuenta.variaciones_porcentuales[anio] || 0)}
                            </TableCell>
                        ))}
                    </>
                ) : (
                    <>
                        {/* Valores por año */}
                        {analisisData!.anios.map((anio) => (
                            <TableCell key={`val-${anio}`} className="text-right">
                                {formatearMoneda(cuenta.valores[anio] || 0)}
                            </TableCell>
                        ))}
                        
                        {/* Porcentajes verticales */}
                        {analisisData!.anios.map((anio) => (
                            <TableCell key={`pct-${anio}`} className="text-right font-semibold text-blue-600">
                                {formatearPorcentaje(cuenta.porcentajes_verticales?.[anio] || 0)}
                            </TableCell>
                        ))}
                    </>
                )}
            </TableRow>
        );
    };

    return (
        <AppLayout>
            <Head title={`Análisis ${tipoAnalisis === 'horizontal' ? 'Horizontal' : 'Vertical'}`} />
            
            <div className="container mx-auto px-6 py-8 space-y-6">
                <div>
                    <h1 className="text-3xl font-bold">
                        Análisis {tipoAnalisis === 'horizontal' ? 'Horizontal' : 'Vertical'}
                    </h1>
                    <p className="text-muted-foreground">
                        {tipoAnalisis === 'horizontal' 
                            ? 'Comparación temporal del Balance General'
                            : 'Análisis de estructura del Estado de Resultados'
                        }
                    </p>
                </div>

                {/* Filtros */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>Seleccione el tipo de análisis, empresa y período</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Tipo de Análisis</label>
                                <Select value={tipoAnalisis} onValueChange={handleTipoAnalisisChange}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="horizontal">Horizontal (Balance)</SelectItem>
                                        <SelectItem value="vertical">Vertical (Resultados)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Año Inicio</label>
                                <Select 
                                    value={anioInicio} 
                                    onValueChange={setAnioInicio}
                                    disabled={aniosDisponibles.length === 0}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder={aniosDisponibles.length === 0 ? "No hay años disponibles" : "Seleccionar año"} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {aniosDisponibles.map((anio) => (
                                            <SelectItem key={anio} value={anio.toString()}>
                                                {anio}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Año Fin</label>
                                <Select 
                                    value={anioFin} 
                                    onValueChange={setAnioFin}
                                    disabled={!anioInicio || aniosDisponibles.length === 0}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder={aniosDisponibles.length === 0 ? "No hay años disponibles" : "Seleccionar año"} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {aniosDisponibles
                                            .filter(a => !anioInicio || a >= parseInt(anioInicio))
                                            .map((anio) => (
                                                <SelectItem key={anio} value={anio.toString()}>
                                                    {anio}
                                                </SelectItem>
                                            ))
                                        }
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-end">
                                <Button 
                                    onClick={handleConsultar}
                                    disabled={!anioInicio || !anioFin}
                                    className="w-full"
                                >
                                    Consultar
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{empresa.nombre}</CardTitle>
                        <CardDescription>
                            Sector: {empresa.sector.nombre} | 
                            Período: {anioInicioServer} - {anioFinServer} |
                            Tipo: {tipoAnalisis === 'horizontal' ? 'Balance General' : 'Estado de Resultados'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {!analisisData ? (
                            <div className="text-center py-8 text-muted-foreground">
                                {anioInicioServer && anioFinServer 
                                    ? `No hay suficientes datos de ${tipoAnalisis === 'horizontal' ? 'Balance General' : 'Estado de Resultados'} para realizar el análisis en el período seleccionado.`
                                    : "Seleccione un período para ver el análisis."
                                }
                            </div>
                        ) : (
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-[100px] sticky left-0">Código</TableHead>
                                                <TableHead className="min-w-[250px] sticky left-[100px]">Cuenta</TableHead>
                                                
                                                {tipoAnalisis === 'horizontal' ? (
                                                    <>
                                                        {analisisData.anios.map((anio) => (
                                                            <TableHead key={anio} className="text-right min-w-[120px]">
                                                                {anio}
                                                            </TableHead>
                                                        ))}
                                                        {analisisData.anios.slice(1).map((anio) => (
                                                            <TableHead key={`var-${anio}`} className="text-right min-w-[120px]">
                                                                Var. Absoluta
                                                            </TableHead>
                                                        ))}
                                                        {analisisData.anios.slice(1).map((anio) => (
                                                            <TableHead key={`per-${anio}`} className="text-right min-w-[100px]">
                                                                Var. Relativa
                                                            </TableHead>
                                                        ))}
                                                    </>
                                                ) : (
                                                    <>
                                                        {analisisData.anios.map((anio) => (
                                                            <TableHead key={`val-${anio}`} className="text-right min-w-[120px]">
                                                                {anio}
                                                            </TableHead>
                                                        ))}
                                                        {analisisData.anios.map((anio) => (
                                                            <TableHead key={`pct-${anio}`} className="text-right min-w-[100px]">
                                                                % {anio}
                                                            </TableHead>
                                                        ))}
                                                    </>
                                                )}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {analisisData.cuentas.map((cuenta) => renderFila(cuenta))}
                                        </TableBody>
                                    </Table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
            </div>
        </AppLayout>
    );
}