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
import { Progress } from '@/components/ui/progress';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import {
    Empresa,
    MetricasResumen,
    PreviewBenchmark,
    PreviewEvolucion,
    PreviewPromedio,
} from '@/types/ratios';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowDownRight,
    ArrowUpRight,
    BarChart3,
    Calendar,
    Minus,
    Target,
    TrendingUp,
    Users,
} from 'lucide-react';
interface Props {
    empresa: Empresa;
    anios_disponibles: number[];
    anio_seleccionado: number;
    tiene_datos: boolean;
    metricas_resumen: MetricasResumen;
    preview_benchmark: PreviewBenchmark;
    preview_promedio: PreviewPromedio;
    preview_evolucion: PreviewEvolucion;
}

export default function Dashboard({
    empresa,
    anios_disponibles,
    anio_seleccionado,
    tiene_datos,
    metricas_resumen,
    preview_benchmark,
    preview_promedio,
    preview_evolucion,
}: Props) {
    const BREADCRUMBS: BreadcrumbItem[] = [
        { title: 'Empresas', href: '/empresas' },
        { title: empresa.nombre, href: `/empresas/${empresa.id}` },
        { title: 'Análisis de Ratios', href: '' },
    ];

    const handleYearChange = (year: string) => {
        router.visit(`/empresas/${empresa.id}/analisis/ratios?anio=${year}`, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getTrendIcon = (trend: string) => {
        switch (trend) {
            case 'ascendente':
                return <ArrowUpRight className="size-5 text-green-600" />;
            case 'descendente':
                return <ArrowDownRight className="size-5 text-red-600" />;
            case 'estable':
                return <Minus className="size-5 text-blue-600" />;
            default:
                return <Minus className="size-5 text-gray-400" />;
        }
    };

    const getTrendText = (trend: string) => {
        switch (trend) {
            case 'ascendente':
                return 'Tendencia positiva';
            case 'descendente':
                return 'Tendencia negativa';
            case 'estable':
                return 'Tendencia estable';
            default:
                return 'Sin datos';
        }
    };

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title={`Análisis de Ratios - ${empresa.nombre}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            {empresa.nombre}
                        </h1>
                        <div className="mt-2 flex items-center gap-2">
                            <Badge variant="outline">{empresa.sector}</Badge>
                            {tiene_datos && (
                                <span className="text-sm text-muted-foreground">
                                    Último año: {anio_seleccionado}
                                </span>
                            )}
                        </div>
                    </div>

                    {/* Selector de año */}
                    {anios_disponibles.length > 0 && (
                        <div className="flex items-center gap-2">
                            <Calendar className="size-4 text-muted-foreground" />
                            <Select
                                value={anio_seleccionado.toString()}
                                onValueChange={handleYearChange}
                            >
                                <SelectTrigger className="w-[140px]">
                                    <SelectValue placeholder="Año" />
                                </SelectTrigger>
                                <SelectContent>
                                    {anios_disponibles.map((year) => (
                                        <SelectItem
                                            key={year}
                                            value={year.toString()}
                                        >
                                            {year}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                </div>

                {/* Sin datos */}
                {!tiene_datos && (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <AlertCircle className="mb-4 size-12 text-muted-foreground" />
                            <h3 className="mb-2 text-lg font-semibold">
                                No hay datos disponibles
                            </h3>
                            <p className="text-center text-muted-foreground">
                                No hay ratios calculados para esta empresa.
                                Primero debes cargar estados financieros.
                            </p>
                        </CardContent>
                    </Card>
                )}

                {/* Contenido con datos */}
                {tiene_datos && (
                    <>
                        {/* Métricas resumen ejecutivo - MEJORADO */}
                        <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                            {/* Total de ratios */}
                            <Card className="transition-shadow hover:shadow-md">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                                    <CardTitle className="text-sm font-medium">
                                        Total de Ratios
                                    </CardTitle>
                                    <div className="rounded-lg bg-muted p-2">
                                        <BarChart3 className="size-4 text-muted-foreground" />
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-1">
                                    <div className="text-3xl font-bold">
                                        {metricas_resumen.total_ratios}
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        Ratios monitoreados
                                    </p>
                                </CardContent>
                            </Card>

                            {/* Mejor categoría */}
                            {metricas_resumen.mejor_categoria && (
                                <Card className="transition-shadow hover:shadow-md">
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                                        <CardTitle className="text-sm font-medium">
                                            Mejor Categoría
                                        </CardTitle>
                                        <div className="rounded-lg bg-green-100 p-2 dark:bg-green-950">
                                            <TrendingUp className="size-4 text-green-600 dark:text-green-400" />
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-1">
                                        <div className="text-3xl font-bold text-green-600 dark:text-green-400">
                                            {
                                                metricas_resumen.mejor_categoria
                                                    .porcentaje
                                            }
                                            %
                                        </div>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {
                                                metricas_resumen.mejor_categoria
                                                    .nombre
                                            }
                                        </p>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Categoría con oportunidad */}
                            {metricas_resumen.categoria_oportunidad && (
                                <Card className="transition-shadow hover:shadow-md">
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                                        <CardTitle className="text-sm font-medium">
                                            Oportunidad de Mejora
                                        </CardTitle>
                                        <div className="rounded-lg bg-orange-100 p-2 dark:bg-orange-950">
                                            <Target className="size-4 text-orange-600 dark:text-orange-400" />
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-1">
                                        <div className="text-3xl font-bold text-orange-600 dark:text-orange-400">
                                            {
                                                metricas_resumen
                                                    .categoria_oportunidad
                                                    .porcentaje
                                            }
                                            %
                                        </div>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {
                                                metricas_resumen
                                                    .categoria_oportunidad
                                                    .nombre
                                            }
                                        </p>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Mejor mejora temporal */}
                            {metricas_resumen.mejor_mejora && (
                                <Card className="transition-shadow hover:shadow-md">
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                                        <CardTitle className="text-sm font-medium">
                                            Mayor Mejora
                                        </CardTitle>
                                        <div className="rounded-lg bg-emerald-100 p-2 dark:bg-emerald-950">
                                            <ArrowUpRight className="size-4 text-emerald-600 dark:text-emerald-400" />
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-1">
                                        <div className="text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                                            +
                                            {
                                                metricas_resumen.mejor_mejora
                                                    .variacion
                                            }
                                            %
                                        </div>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {
                                                metricas_resumen.mejor_mejora
                                                    .nombre
                                            }
                                        </p>
                                    </CardContent>
                                </Card>
                            )}
                        </div>

                        {/* Cards principales de navegación - MEJORADO */}
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {/* Card 1: Benchmark */}
                            <Card className="flex flex-col transition-shadow hover:shadow-lg">
                                <CardHeader className="pb-4">
                                    <div className="flex items-start gap-4">
                                        <div className="shrink-0 rounded-lg bg-blue-100 p-3 dark:bg-blue-950">
                                            <Target className="size-6 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <div className="flex-1 space-y-2">
                                            <CardTitle className="text-lg leading-tight">
                                                Comparación con Benchmark
                                            </CardTitle>
                                            <CardDescription className="text-sm leading-relaxed">
                                                Compara tus ratios con los
                                                estándares oficiales del sector
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="flex flex-1 flex-col space-y-4">
                                    {/* Preview */}
                                    <div className="flex-1 space-y-3 rounded-lg bg-muted/50 p-4">
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="font-medium text-muted-foreground">
                                                Cumplimiento
                                            </span>
                                            <span className="font-semibold">
                                                {
                                                    preview_benchmark.ratios_cumplen
                                                }{' '}
                                                de{' '}
                                                {preview_benchmark.total_ratios}
                                            </span>
                                        </div>
                                        <Progress
                                            value={preview_benchmark.porcentaje}
                                            className="h-2.5"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            <span className="font-semibold text-blue-600 dark:text-blue-400">
                                                {preview_benchmark.porcentaje}%
                                            </span>{' '}
                                            de ratios cumplen el benchmark
                                        </p>
                                    </div>
                                    <Button
                                        asChild
                                        className="w-full"
                                        variant="outline"
                                    >
                                        <Link
                                            href={`/empresas/${empresa.id}/analisis/ratios/benchmark/${anio_seleccionado}`}
                                        >
                                            Ver análisis detallado
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>

                            {/* Card 2: Promedio del sector */}
                            <Card className="flex flex-col transition-shadow hover:shadow-lg">
                                <CardHeader className="pb-4">
                                    <div className="flex items-start gap-4">
                                        <div className="shrink-0 rounded-lg bg-green-100 p-3 dark:bg-green-950">
                                            <Users className="size-6 text-green-600 dark:text-green-400" />
                                        </div>
                                        <div className="flex-1 space-y-2">
                                            <CardTitle className="text-lg leading-tight">
                                                Comparación con Promedio
                                            </CardTitle>
                                            <CardDescription className="text-sm leading-relaxed">
                                                Compara tu desempeño con el
                                                promedio de empresas similares
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {/* Preview */}
                                    <div className="space-y-3 rounded-lg bg-muted/50 p-4">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm font-medium text-muted-foreground">
                                                Posición general
                                            </span>
                                            <Badge
                                                variant={
                                                    preview_promedio.superiores >
                                                    0
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {preview_promedio.superiores > 0
                                                    ? 'Superior'
                                                    : 'En promedio'}
                                            </Badge>
                                        </div>
                                        <p className="text-sm leading-relaxed">
                                            Superior al promedio en{' '}
                                            <span className="font-semibold">
                                                {preview_promedio.superiores} de{' '}
                                                {
                                                    preview_promedio.total_categorias
                                                }
                                            </span>{' '}
                                            categorías
                                        </p>
                                    </div>
                                    <Button
                                        asChild
                                        className="w-full"
                                        variant="outline"
                                    >
                                        <Link
                                            href={`/empresas/${empresa.id}/analisis/ratios/promedio-sector/${anio_seleccionado}`}
                                        >
                                            Ver análisis detallado
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>

                            {/* Card 3: Evolución temporal */}
                            <Card className="transition-shadow hover:shadow-lg">
                                <CardHeader className="pb-4">
                                    <div className="flex items-start gap-4">
                                        <div className="shrink-0 rounded-lg bg-purple-100 p-3 dark:bg-purple-950">
                                            <TrendingUp className="size-6 text-purple-600 dark:text-purple-400" />
                                        </div>
                                        <div className="flex-1 space-y-2">
                                            <CardTitle className="text-lg leading-tight">
                                                Evolución Temporal
                                            </CardTitle>
                                            <CardDescription className="text-sm leading-relaxed">
                                                Visualiza la evolución histórica
                                                de tus indicadores financieros
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {/* Preview */}
                                    <div className="space-y-3 rounded-lg bg-muted/50 p-4">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm font-medium text-muted-foreground">
                                                Años de datos
                                            </span>
                                            <span className="text-2xl font-bold">
                                                {preview_evolucion.anios_datos}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {getTrendIcon(
                                                preview_evolucion.tendencia_general,
                                            )}
                                            <span className="text-sm font-medium">
                                                {getTrendText(
                                                    preview_evolucion.tendencia_general,
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                    <Button
                                        asChild
                                        className="w-full"
                                        variant="outline"
                                    >
                                        <Link
                                            href={`/empresas/${empresa.id}/analisis/ratios/evolucion`}
                                        >
                                            Ver evolución completa
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
