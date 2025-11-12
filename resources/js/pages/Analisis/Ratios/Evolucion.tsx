'use client';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartConfig,
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectSeparator,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { RatioData } from '@/types/ratios';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Minus, TrendingDown, TrendingUp } from 'lucide-react';
import React from 'react';
import { CartesianGrid, Line, LineChart, XAxis, YAxis } from 'recharts';

interface Empresa {
    id: number;
    nombre: string;
    sector?: {
        nombre: string;
    };
}

interface Props {
    empresa: Empresa;
    anios_disponibles: number[];
    ratios: Record<string, RatioData>;
}

export default function Evolucion({
    empresa,
    anios_disponibles,
    ratios,
}: Props) {
    const BREADCRUMBS: BreadcrumbItem[] = [
        { title: 'Empresas', href: '/empresas' },
        {
            title: empresa.nombre,
            href: `/empresas/${empresa.id}/analisis/ratios`,
        },
        { title: 'Evolución de Ratios', href: '' },
    ];

    // Obtener el primer ratio disponible
    const primeraClaveRatio = Object.keys(ratios)[0];
    const [ratioSeleccionado, setRatioSeleccionado] = React.useState<string>(
        primeraClaveRatio || '',
    );

    const ratioActual = ratios[ratioSeleccionado];

    // Preparar datos para la gráfica
    const prepararDatosGrafica = () => {
        if (!ratioActual) return [];

        return ratioActual.serie_empresa.map((punto) => {
            const promedioAnio = ratioActual.promedios_sector.find(
                (p) => p.anio === punto.anio,
            );

            return {
                anio: punto.anio.toString(),
                empresa: punto.valor,
                promedio_sector: promedioAnio?.valor || null,
                benchmark: ratioActual.benchmark_sector?.valor || null,
            };
        });
    };

    const datosGrafica = prepararDatosGrafica();

    // Configuración del chart
    const chartConfig = {
        empresa: {
            label: 'Tu Empresa',
            color: 'var(--chart-1)',
        },
        promedio_sector: {
            label: 'Promedio Sector',
            color: 'var(--chart-2)',
        },
        benchmark: {
            label: 'Benchmark',
            color: 'var(--chart-3)',
        },
    } satisfies ChartConfig;

    // Agrupar ratios por categoría
    const ratiosPorCategoria = React.useMemo(() => {
        const categorias: Record<
            string,
            Array<{ clave: string; nombre: string }>
        > = {
            Liquidez: [],
            Rentabilidad: [],
            Endeudamiento: [],
            Actividad: [],
        };

        Object.entries(ratios).forEach(([clave, ratio]) => {
            if (categorias[ratio.categoria]) {
                categorias[ratio.categoria].push({
                    clave,
                    nombre: ratio.nombre_ratio,
                });
            }
        });

        return categorias;
    }, [ratios]);

    // Funciones helper para tendencia
    const getTrendIcon = (direccion: string) => {
        switch (direccion) {
            case 'ascendente':
                return <TrendingUp className="size-4 text-green-600" />;
            case 'descendente':
                return <TrendingDown className="size-4 text-red-600" />;
            default:
                return <Minus className="size-4 text-muted-foreground" />;
        }
    };

    const getTrendText = (direccion: string): string => {
        switch (direccion) {
            case 'ascendente':
                return 'Creciente';
            case 'descendente':
                return 'Decreciente';
            case 'estable':
                return 'Estable';
            default:
                return 'Sin datos';
        }
    };

    const getTrendVariant = (direccion: string) => {
        switch (direccion) {
            case 'ascendente':
                return 'default';
            case 'descendente':
                return 'destructive';
            default:
                return 'secondary';
        }
    };

    if (!ratioActual) {
        return (
            <AppLayout breadcrumbs={BREADCRUMBS}>
                <Head title={`Evolución de Ratios - ${empresa.nombre}`} />
                <div className="flex h-full items-center justify-center p-6">
                    <Card className="w-full max-w-md">
                        <CardContent className="pt-6 text-center">
                            <p className="text-muted-foreground">
                                No hay datos de ratios disponibles
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title={`Evolución de Ratios - ${empresa.nombre}`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-2">
                        <h1 className="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Evolución de Ratios Financieros
                        </h1>
                        <p className="text-base text-slate-600 dark:text-slate-400">
                            Análisis temporal del desempeño de {empresa.nombre}
                        </p>
                        {empresa.sector && (
                            <Badge variant="outline" className="text-sm">
                                {empresa.sector.nombre}
                            </Badge>
                        )}
                    </div>

                    <Button asChild variant="outline" className="shrink-0">
                        <Link href={`/empresas/${empresa.id}/analisis/ratios`}>
                            <ArrowLeft className="mr-2 size-4" />
                            Volver
                        </Link>
                    </Button>
                </div>

                {/* Selector de Ratio */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">
                            Selecciona un Ratio
                        </CardTitle>
                        <CardDescription>
                            Analiza la evolución de los diferentes indicadores
                            financieros
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Select
                            value={ratioSeleccionado}
                            onValueChange={setRatioSeleccionado}
                        >
                            <SelectTrigger className="w-full sm:w-[400px]">
                                <SelectValue placeholder="Selecciona un ratio" />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(ratiosPorCategoria).map(
                                    ([categoria, ratiosCategoria]) =>
                                        ratiosCategoria.length > 0 && (
                                            <React.Fragment key={categoria}>
                                                <SelectGroup>
                                                    <SelectLabel>
                                                        {categoria}
                                                    </SelectLabel>
                                                    {ratiosCategoria.map(
                                                        (ratio) => (
                                                            <SelectItem
                                                                key={
                                                                    ratio.clave
                                                                }
                                                                value={
                                                                    ratio.clave
                                                                }
                                                            >
                                                                {ratio.nombre}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectGroup>
                                                <SelectSeparator />
                                            </React.Fragment>
                                        ),
                                )}
                            </SelectContent>
                        </Select>
                    </CardContent>
                </Card>

                {/* Métricas del Ratio Seleccionado */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {/* Variación */}
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Variación Total
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-baseline gap-2">
                                <span
                                    className={cn(
                                        'text-2xl font-bold tabular-nums',
                                        ratioActual.tendencia.variacion > 0
                                            ? 'text-green-600'
                                            : ratioActual.tendencia.variacion <
                                                0
                                              ? 'text-red-600'
                                              : 'text-muted-foreground',
                                    )}
                                >
                                    {ratioActual.tendencia.variacion > 0
                                        ? '+'
                                        : ''}
                                    {ratioActual.tendencia.variacion !== undefined
                                        ? ratioActual.tendencia.variacion.toFixed(1)
                                        : '0.0'}
                                    %
                                </span>
                                {getTrendIcon(ratioActual.tendencia.direccion)}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Valor Final */}
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Valor Actual (
                                {
                                    anios_disponibles[
                                        anios_disponibles.length - 1
                                    ]
                                }
                                )
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold text-slate-900 tabular-nums dark:text-white">
                                {ratioActual.tendencia.valor_final !== undefined
                                    ? ratioActual.tendencia.valor_final.toFixed(2)
                                    : 'N/A'}
                            </p>
                        </CardContent>
                    </Card>

                    {/* Tendencia */}
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Tendencia
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Badge
                                variant={getTrendVariant(
                                    ratioActual.tendencia.direccion,
                                )}
                                className="text-sm"
                            >
                                {getTrendText(ratioActual.tendencia.direccion)}
                            </Badge>
                        </CardContent>
                    </Card>

                    {/* Años Analizados */}
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Años Analizados
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold text-slate-900 tabular-nums dark:text-white">
                                {anios_disponibles.length}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {anios_disponibles[0]} -{' '}
                                {
                                    anios_disponibles[
                                        anios_disponibles.length - 1
                                    ]
                                }
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Gráfica */}
                <Card>
                    <CardHeader>
                        <CardTitle>{ratioActual.nombre_ratio}</CardTitle>
                        <CardDescription className="font-mono text-xs">
                            {ratioActual.formula}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ChartContainer
                            config={chartConfig}
                            className="min-h-[400px] w-full"
                        >
                            <LineChart
                                accessibilityLayer
                                data={datosGrafica}
                                margin={{
                                    left: 12,
                                    right: 12,
                                    top: 12,
                                    bottom: 12,
                                }}
                            >
                                <CartesianGrid
                                    vertical={false}
                                    strokeDasharray="3 3"
                                />
                                <XAxis
                                    dataKey="anio"
                                    tickLine={false}
                                    axisLine={false}
                                    tickMargin={8}
                                />
                                <YAxis
                                    tickLine={false}
                                    axisLine={false}
                                    tickMargin={8}
                                    tickFormatter={(value) => value.toFixed(1)}
                                />
                                <ChartTooltip
                                    cursor={false}
                                    content={
                                        <ChartTooltipContent
                                            labelFormatter={(value) =>
                                                `Año ${value}`
                                            }
                                            formatter={(value) =>
                                                typeof value === 'number'
                                                    ? value.toFixed(2)
                                                    : value
                                            }
                                        />
                                    }
                                />
                                <ChartLegend content={<ChartLegendContent />} />

                                {/* Línea de la empresa */}
                                <Line
                                    type="monotone"
                                    dataKey="empresa"
                                    stroke="var(--color-empresa)"
                                    strokeWidth={3}
                                    dot={{ r: 4, strokeWidth: 2 }}
                                    activeDot={{ r: 6 }}
                                />

                                {/* Línea de promedio sector */}
                                {datosGrafica.some(
                                    (d) => d.promedio_sector !== null,
                                ) && (
                                    <Line
                                        type="monotone"
                                        dataKey="promedio_sector"
                                        stroke="var(--color-promedio_sector)"
                                        strokeWidth={2}
                                        dot={{ r: 3 }}
                                    />
                                )}

                                {/* Línea de benchmark (constante) */}
                                {ratioActual.benchmark_sector && (
                                    <Line
                                        type="monotone"
                                        dataKey="benchmark"
                                        stroke="var(--color-benchmark)"
                                        strokeWidth={2}
                                        strokeDasharray="5 5"
                                        dot={false}
                                    />
                                )}
                            </LineChart>
                        </ChartContainer>
                    </CardContent>
                </Card>

                {/* Información Adicional */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">
                            Información del Ratio
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <dt className="text-sm font-medium text-muted-foreground">
                                    Categoría
                                </dt>
                                <dd className="mt-1 text-sm font-semibold">
                                    {ratioActual.categoria}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-muted-foreground">
                                    Fórmula
                                </dt>
                                <dd className="mt-1 font-mono text-sm">
                                    {ratioActual.formula}
                                </dd>
                            </div>
                            {ratioActual.benchmark_sector && (
                                <>
                                    <div>
                                        <dt className="text-sm font-medium text-muted-foreground">
                                            Benchmark del Sector
                                        </dt>
                                        <dd className="mt-1 text-sm font-semibold tabular-nums">
                                            {ratioActual.benchmark_sector.valor.toFixed(
                                                2,
                                            )}
                                        </dd>
                                    </div>
                                    <div className="sm:col-span-2">
                                        <dt className="text-sm font-medium text-muted-foreground">
                                            Fuente del Benchmark
                                        </dt>
                                        <dd className="mt-1 text-sm">
                                            {ratioActual.benchmark_sector
                                                .fuente || 'No especificada'}
                                        </dd>
                                    </div>
                                </>
                            )}
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
