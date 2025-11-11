'use client';

import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { Comparacion } from '@/types/ratios';
import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    ArrowLeft,
    CheckCircle2,
    DollarSign,
    Info,
    TrendingDown,
    TrendingUp,
    XCircle,
} from 'lucide-react';

interface Empresa {
    id: number;
    nombre: string;
    sector?: {
        nombre: string;
    };
}

interface Props {
    empresa: Empresa;
    anio: number;
    comparaciones: Comparacion[];
    comparaciones_por_categoria: Record<string, Comparacion[]>;
}

export default function ComparacionBenchmark({
    empresa,
    anio,
    comparaciones,
    comparaciones_por_categoria,
}: Props) {
    const BREADCRUMBS: BreadcrumbItem[] = [
        { title: 'Empresas', href: '/empresas' },
        {
            title: empresa.nombre,
            href: `/empresas/${empresa.id}/analisis/ratios`,
        },
        { title: 'Comparación con Benchmark', href: '' },
    ];

    // Función para obtener el icono de categoría
    const getCategoryIcon = (categoria: string) => {
        switch (categoria) {
            case 'Liquidez':
                return <DollarSign className="size-5" />;
            case 'Rentabilidad':
                return <TrendingUp className="size-5" />;
            case 'Endeudamiento':
                return <TrendingDown className="size-5" />;
            case 'Actividad':
                return <Activity className="size-5" />;
            default:
                return <Info className="size-5" />;
        }
    };

    // Función para obtener el badge según el estado
    const getEstadoBadge = (estado?: string, cumple?: boolean) => {
        if (!estado) return null;

        const variants: Record<
            string,
            {
                className: string;
                label: string;
            }
        > = {
            success: {
                className:
                    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                label: cumple ? 'Cumple' : 'Superior',
            },
            info: {
                className:
                    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                label: 'Aceptable',
            },
            neutral: {
                className:
                    'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                label: 'Neutral',
            },
            warning: {
                className:
                    'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                label: 'Atención',
            },
            danger: {
                className:
                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                label: 'No Cumple',
            },
        };

        const config = variants[estado] || variants.neutral;

        return (
            <span
                className={cn(
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                    config.className,
                )}
            >
                {config.label}
            </span>
        );
    };

    // Calcular estadísticas generales
    const totalRatios = comparaciones.length;
    const ratiosCumplen = comparaciones.filter(
        (c) => c.benchmark?.cumple,
    ).length;
    const porcentajeCumplimiento =
        totalRatios > 0 ? (ratiosCumplen / totalRatios) * 100 : 0;

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title={`Comparación con Benchmark - ${empresa.nombre}`} />

            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-3">
                        <h1 className="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Comparación con Benchmark
                        </h1>
                        <p className="text-base text-slate-600 dark:text-slate-400">
                            Evaluación de ratios contra estándares del sector
                            para {anio}
                        </p>
                        {empresa.sector && (
                            <div className="flex items-center gap-3">
                                <Badge
                                    variant="outline"
                                    className="font-normal"
                                >
                                    {empresa.sector.nombre}
                                </Badge>
                                <span className="text-sm text-slate-500 dark:text-slate-500">
                                    Año {anio}
                                </span>
                            </div>
                        )}
                    </div>

                    <Button asChild variant="outline" className="shrink-0">
                        <Link href={`/empresas/${empresa.id}/analisis/ratios`}>
                            <ArrowLeft className="mr-2 size-4" />
                            Volver
                        </Link>
                    </Button>
                </div>

                {/* Resumen Ejecutivo */}
                <Card className="border-slate-200 dark:border-slate-800">
                    <CardHeader className="pb-4">
                        <CardTitle className="text-xl font-semibold text-slate-900 dark:text-white">
                            Resumen de Cumplimiento
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-slate-600 dark:text-slate-400">
                                    Ratios que cumplen
                                </p>
                                <p className="mt-1 text-3xl font-bold text-slate-900 dark:text-white">
                                    {ratiosCumplen}{' '}
                                    <span className="text-xl text-slate-400">
                                        / {totalRatios}
                                    </span>
                                </p>
                            </div>
                            <div className="text-right">
                                <p className="text-4xl font-bold text-blue-600 dark:text-blue-400">
                                    {porcentajeCumplimiento.toFixed(0)}%
                                </p>
                                <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                    Total
                                </p>
                            </div>
                        </div>
                        <Progress
                            value={porcentajeCumplimiento}
                            className="h-2.5"
                        />
                    </CardContent>
                </Card>

                {/* Comparaciones por Categoría */}
                <Accordion
                    type="multiple"
                    defaultValue={Object.keys(comparaciones_por_categoria)}
                    className="space-y-4"
                >
                    {Object.entries(comparaciones_por_categoria).map(
                        ([categoria, ratios]) => (
                            <AccordionItem
                                key={categoria}
                                value={categoria}
                                className="rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900"
                            >
                                <AccordionTrigger className="px-6 py-5 hover:no-underline">
                                    <div className="flex items-center gap-4">
                                        <div className="rounded-lg bg-slate-100 p-2.5 dark:bg-slate-800">
                                            {getCategoryIcon(categoria)}
                                        </div>
                                        <div className="text-left">
                                            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">
                                                {categoria}
                                            </h3>
                                            <p className="text-sm text-slate-500 dark:text-slate-500">
                                                {ratios.length} ratio
                                                {ratios.length !== 1 ? 's' : ''}
                                            </p>
                                        </div>
                                    </div>
                                </AccordionTrigger>
                                <AccordionContent className="px-6 pb-6">
                                    <div className="space-y-4 pt-2">
                                        {ratios.map((ratio, index) => (
                                            <div
                                                key={index}
                                                className="rounded-lg border border-slate-200 bg-slate-50/50 p-5 transition-all hover:border-slate-300 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900/50 dark:hover:border-slate-700 dark:hover:bg-slate-900"
                                            >
                                                {/* Header del Ratio */}
                                                <div className="mb-5 flex items-start justify-between gap-4">
                                                    <div className="flex-1 space-y-2">
                                                        <div className="flex items-center gap-3">
                                                            <h4 className="text-base font-semibold text-slate-900 dark:text-white">
                                                                {
                                                                    ratio.nombre_ratio
                                                                }
                                                            </h4>
                                                            {ratio.benchmark &&
                                                                getEstadoBadge(
                                                                    ratio
                                                                        .benchmark
                                                                        .estado,
                                                                    ratio
                                                                        .benchmark
                                                                        .cumple,
                                                                )}
                                                        </div>
                                                        <TooltipProvider>
                                                            <Tooltip>
                                                                <TooltipTrigger
                                                                    asChild
                                                                >
                                                                    <p className="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-500">
                                                                        <Info className="size-3.5" />
                                                                        {
                                                                            ratio.formula
                                                                        }
                                                                    </p>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>
                                                                        Fórmula
                                                                        de
                                                                        cálculo
                                                                    </p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        </TooltipProvider>
                                                    </div>
                                                </div>

                                                {ratio.benchmark ? (
                                                    <>
                                                        {/* Comparación Visual */}
                                                        <div className="mb-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                                                            {/* Valor Empresa */}
                                                            <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/30">
                                                                <p className="mb-1.5 text-xs font-medium tracking-wide text-blue-700 uppercase dark:text-blue-400">
                                                                    Tu Empresa
                                                                </p>
                                                                <p className="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                                                    {ratio.valor_empresa.toFixed(
                                                                        2,
                                                                    )}
                                                                </p>
                                                            </div>

                                                            {/* Diferencia */}
                                                            <div className="flex items-center justify-center">
                                                                <div className="text-center">
                                                                    <p
                                                                        className={cn(
                                                                            'text-2xl font-bold',
                                                                            (ratio
                                                                                .benchmark
                                                                                .diferencia
                                                                                .absoluta ??
                                                                                0) >
                                                                                0
                                                                                ? 'text-green-600 dark:text-green-400'
                                                                                : (ratio
                                                                                        .benchmark
                                                                                        .diferencia
                                                                                        .absoluta ??
                                                                                        0) <
                                                                                    0
                                                                                  ? 'text-red-600 dark:text-red-400'
                                                                                  : 'text-slate-600 dark:text-slate-400',
                                                                        )}
                                                                    >
                                                                        {(ratio
                                                                            .benchmark
                                                                            .diferencia
                                                                            .porcentual ??
                                                                            0) >
                                                                        0
                                                                            ? '+'
                                                                            : ''}
                                                                        {ratio.benchmark.diferencia.porcentual.toFixed(
                                                                            1,
                                                                        )}
                                                                        %
                                                                    </p>
                                                                    <p className="mt-1 text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-500">
                                                                        Diferencia
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            {/* Valor Benchmark */}
                                                            <div className="rounded-lg border border-purple-200 bg-purple-50 p-4 dark:border-purple-900/50 dark:bg-purple-950/30">
                                                                <p className="mb-1.5 text-xs font-medium tracking-wide text-purple-700 uppercase dark:text-purple-400">
                                                                    Benchmark
                                                                </p>
                                                                <p className="text-3xl font-bold text-purple-600 dark:text-purple-400">
                                                                    {ratio.benchmark.valor.toFixed(
                                                                        2,
                                                                    )}
                                                                </p>
                                                                {ratio.benchmark
                                                                    .fuente && (
                                                                    <TooltipProvider>
                                                                        <Tooltip>
                                                                            <TooltipTrigger
                                                                                asChild
                                                                            >
                                                                                <p className="mt-1.5 truncate text-xs text-purple-600/70 dark:text-purple-400/70">
                                                                                    Fuente:{' '}
                                                                                    {
                                                                                        ratio
                                                                                            .benchmark
                                                                                            .fuente
                                                                                    }
                                                                                </p>
                                                                            </TooltipTrigger>
                                                                            <TooltipContent>
                                                                                <p>
                                                                                    {
                                                                                        ratio
                                                                                            .benchmark
                                                                                            .fuente
                                                                                    }
                                                                                </p>
                                                                            </TooltipContent>
                                                                        </Tooltip>
                                                                    </TooltipProvider>
                                                                )}
                                                            </div>
                                                        </div>

                                                        <Separator className="my-4" />

                                                        {/* Interpretación */}
                                                        <div className="flex items-start gap-3">
                                                            {ratio.benchmark
                                                                .cumple ? (
                                                                <CheckCircle2 className="mt-0.5 size-5 shrink-0 text-green-600 dark:text-green-400" />
                                                            ) : (
                                                                <XCircle className="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400" />
                                                            )}
                                                            <p className="text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                                                                {
                                                                    ratio
                                                                        .benchmark
                                                                        .interpretacion
                                                                }
                                                            </p>
                                                        </div>
                                                    </>
                                                ) : (
                                                    <div className="flex items-center gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
                                                        <Info className="size-5 shrink-0 text-slate-400 dark:text-slate-600" />
                                                        <p className="text-sm text-slate-600 dark:text-slate-400">
                                                            No hay benchmark
                                                            disponible para este
                                                            ratio
                                                        </p>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </AccordionContent>
                            </AccordionItem>
                        ),
                    )}
                </Accordion>
            </div>
        </AppLayout>
    );
}
