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
    BarChart3,
    DollarSign,
    Info,
    TrendingDown,
    TrendingUp,
    Users,
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

export default function ComparacionPromedio({
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
        { title: 'Comparación con Promedio del Sector', href: '' },
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

    // Función para obtener el badge según la posición relativa
    const getPosicionBadge = (posicion?: string) => {
        if (!posicion) return null;

        const variants: Record<
            string,
            {
                className: string;
                label: string;
            }
        > = {
            'Muy superior': {
                className:
                    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                label: 'Muy Superior',
            },
            Superior: {
                className:
                    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                label: 'Superior',
            },
            'En el promedio': {
                className:
                    'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                label: 'En el Promedio',
            },
            Inferior: {
                className:
                    'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                label: 'Inferior',
            },
            'Muy inferior': {
                className:
                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                label: 'Muy Inferior',
            },
        };

        const config = variants[posicion] || variants['En el promedio'];

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
    const ratiosSuperiorPromedio = comparaciones.filter(
        (c) =>
            c.promedio_sector?.posicion_relativa === 'Superior' ||
            c.promedio_sector?.posicion_relativa === 'Muy superior',
    ).length;
    const porcentajeSuperior =
        totalRatios > 0 ? (ratiosSuperiorPromedio / totalRatios) * 100 : 0;

    // Obtener cantidad total de empresas comparadas (del primer ratio que tenga datos)
    const cantidadEmpresas =
        comparaciones.find((c) => c.promedio_sector)?.promedio_sector
            ?.cantidad_empresas || 0;

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head
                title={`Comparación Promedio del Sector - ${empresa.nombre}`}
            />

            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-3">
                        <h1 className="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Comparación con Promedio del Sector
                        </h1>
                        <p className="text-base text-slate-600 dark:text-slate-400">
                            Analiza tu desempeño contra el promedio de empresas
                            del sector para {anio}
                        </p>
                        {empresa.sector && (
                            <div className="flex items-center gap-3">
                                <Badge
                                    variant="outline"
                                    className="text-sm font-medium"
                                >
                                    {empresa.sector.nombre}
                                </Badge>
                                {cantidadEmpresas > 0 && (
                                    <div className="flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-400">
                                        <Users className="size-4" />
                                        <span>
                                            {cantidadEmpresas} empresa
                                            {cantidadEmpresas !== 1
                                                ? 's'
                                                : ''}{' '}
                                            comparadas
                                        </span>
                                    </div>
                                )}
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
                            Posicionamiento en el Sector
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-slate-600 dark:text-slate-400">
                                    Ratios por encima del promedio
                                </p>
                                <p className="mt-1 text-3xl font-bold text-slate-900 dark:text-white">
                                    {ratiosSuperiorPromedio} de {totalRatios}
                                </p>
                            </div>
                            <div className="text-right">
                                <p className="text-4xl font-bold text-blue-600 dark:text-blue-400">
                                    {porcentajeSuperior.toFixed(0)}%
                                </p>
                                <p className="mt-1 text-sm font-medium text-slate-600 dark:text-slate-400">
                                    Por encima
                                </p>
                            </div>
                        </div>
                        <Progress
                            value={porcentajeSuperior}
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
                                            <p className="text-sm text-slate-600 dark:text-slate-400">
                                                {ratios.length} ratio
                                                {ratios.length !== 1 ? 's' : ''}
                                            </p>
                                        </div>
                                    </div>
                                </AccordionTrigger>
                                <AccordionContent className="px-6 pb-6">
                                    <div className="space-y-4 pt-2">
                                        {ratios.map((ratio, index) => (
                                            <Card
                                                key={index}
                                                className="border-slate-200 transition-shadow hover:shadow-md dark:border-slate-700"
                                            >
                                                <CardContent className="p-5">
                                                    {/* Header del Ratio */}
                                                    <div className="mb-5 flex items-start justify-between gap-4">
                                                        <div className="flex-1 space-y-2">
                                                            <div className="flex items-center gap-2.5">
                                                                <h4 className="text-base font-semibold text-slate-900 dark:text-white">
                                                                    {
                                                                        ratio.nombre_ratio
                                                                    }
                                                                </h4>
                                                                {ratio.promedio_sector &&
                                                                    getPosicionBadge(
                                                                        ratio
                                                                            .promedio_sector
                                                                            .posicion_relativa,
                                                                    )}
                                                            </div>
                                                            <TooltipProvider>
                                                                <Tooltip>
                                                                    <TooltipTrigger
                                                                        asChild
                                                                    >
                                                                        <p className="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
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

                                                    {ratio.promedio_sector ? (
                                                        <>
                                                            {/* Comparación Visual con Rango */}
                                                            <div className="mb-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                                                                {/* Valor Empresa */}
                                                                <div className="space-y-2 rounded-lg bg-blue-50 p-4 dark:bg-blue-950/20">
                                                                    <div className="flex items-center gap-2">
                                                                        <BarChart3 className="size-4 text-blue-600 dark:text-blue-400" />
                                                                        <p className="text-xs font-medium tracking-wide text-slate-600 uppercase dark:text-slate-400">
                                                                            Tu
                                                                            Empresa
                                                                        </p>
                                                                    </div>
                                                                    <p className="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                                                        {ratio.valor_empresa.toFixed(
                                                                            2,
                                                                        )}
                                                                    </p>
                                                                </div>

                                                                {/* Diferencia */}
                                                                <div className="flex items-center justify-center rounded-lg bg-slate-50 p-4 dark:bg-slate-800/50">
                                                                    <div className="text-center">
                                                                        <p
                                                                            className={cn(
                                                                                'text-3xl font-bold',
                                                                                (ratio
                                                                                    .promedio_sector
                                                                                    .diferencia
                                                                                    .absoluta ??
                                                                                    0) >
                                                                                    0
                                                                                    ? 'text-green-600 dark:text-green-400'
                                                                                    : (ratio
                                                                                            .promedio_sector
                                                                                            .diferencia
                                                                                            .absoluta ??
                                                                                            0) <
                                                                                        0
                                                                                      ? 'text-red-600 dark:text-red-400'
                                                                                      : 'text-slate-600 dark:text-slate-400',
                                                                            )}
                                                                        >
                                                                            {(ratio
                                                                                .promedio_sector
                                                                                .diferencia
                                                                                .porcentual ??
                                                                                0) >
                                                                            0
                                                                                ? '+'
                                                                                : ''}
                                                                            {ratio.promedio_sector.diferencia.porcentual.toFixed(
                                                                                1,
                                                                            )}
                                                                            %
                                                                        </p>
                                                                        <p className="mt-1 text-xs font-medium tracking-wide text-slate-600 uppercase dark:text-slate-400">
                                                                            vs
                                                                            Promedio
                                                                        </p>
                                                                    </div>
                                                                </div>

                                                                {/* Promedio del Sector */}
                                                                <div className="space-y-2 rounded-lg bg-purple-50 p-4 dark:bg-purple-950/20">
                                                                    <div className="flex items-center gap-2">
                                                                        <Users className="size-4 text-purple-600 dark:text-purple-400" />
                                                                        <p className="text-xs font-medium tracking-wide text-slate-600 uppercase dark:text-slate-400">
                                                                            Promedio
                                                                            Sector
                                                                        </p>
                                                                    </div>
                                                                    <p className="text-3xl font-bold text-purple-600 dark:text-purple-400">
                                                                        {ratio.promedio_sector.valor.toFixed(
                                                                            2,
                                                                        )}
                                                                    </p>
                                                                    <TooltipProvider>
                                                                        <Tooltip>
                                                                            <TooltipTrigger
                                                                                asChild
                                                                            >
                                                                                <p className="truncate text-xs text-slate-500 dark:text-slate-400">
                                                                                    Basado
                                                                                    en{' '}
                                                                                    {
                                                                                        ratio
                                                                                            .promedio_sector
                                                                                            .cantidad_empresas
                                                                                    }{' '}
                                                                                    empresa
                                                                                    {ratio
                                                                                        .promedio_sector
                                                                                        .cantidad_empresas !==
                                                                                    1
                                                                                        ? 's'
                                                                                        : ''}
                                                                                </p>
                                                                            </TooltipTrigger>
                                                                            <TooltipContent>
                                                                                <p>
                                                                                    Promedio
                                                                                    calculado
                                                                                    a
                                                                                    partir
                                                                                    de{' '}
                                                                                    {
                                                                                        ratio
                                                                                            .promedio_sector
                                                                                            .cantidad_empresas
                                                                                    }{' '}
                                                                                    empresa
                                                                                    {ratio
                                                                                        .promedio_sector
                                                                                        .cantidad_empresas !==
                                                                                    1
                                                                                        ? 's'
                                                                                        : ''}{' '}
                                                                                    del
                                                                                    sector
                                                                                </p>
                                                                            </TooltipContent>
                                                                        </Tooltip>
                                                                    </TooltipProvider>
                                                                </div>
                                                            </div>

                                                            {/* Rango del Sector */}
                                                            <div className="mb-5 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/30">
                                                                <div className="mb-3 flex items-center justify-between text-sm">
                                                                    <span className="font-medium text-slate-700 dark:text-slate-300">
                                                                        Rango
                                                                        del
                                                                        Sector
                                                                    </span>
                                                                    <div className="flex items-center gap-4">
                                                                        <span className="text-slate-600 dark:text-slate-400">
                                                                            Mín:{' '}
                                                                            <span className="font-semibold text-slate-900 dark:text-white">
                                                                                {ratio.promedio_sector.minimo.toFixed(
                                                                                    2,
                                                                                )}
                                                                            </span>
                                                                        </span>
                                                                        <span className="text-slate-600 dark:text-slate-400">
                                                                            Máx:{' '}
                                                                            <span className="font-semibold text-slate-900 dark:text-white">
                                                                                {ratio.promedio_sector.maximo.toFixed(
                                                                                    2,
                                                                                )}
                                                                            </span>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                {/* Barra visual del rango */}
                                                                <div className="relative h-3 rounded-full bg-slate-200 dark:bg-slate-700">
                                                                    {/* Indicador del promedio */}
                                                                    <div
                                                                        className="absolute top-0 h-full w-1 bg-purple-500"
                                                                        style={{
                                                                            left: `${((ratio.promedio_sector.valor - ratio.promedio_sector.minimo) / (ratio.promedio_sector.maximo - ratio.promedio_sector.minimo)) * 100}%`,
                                                                        }}
                                                                    />
                                                                    {/* Indicador de la empresa */}
                                                                    <div
                                                                        className="absolute -top-1 size-5 rounded-full border-2 border-white bg-blue-500 shadow-md dark:border-slate-900"
                                                                        style={{
                                                                            left: `${Math.min(Math.max(((ratio.valor_empresa - ratio.promedio_sector.minimo) / (ratio.promedio_sector.maximo - ratio.promedio_sector.minimo)) * 100, 0), 100)}%`,
                                                                            transform:
                                                                                'translateX(-50%)',
                                                                        }}
                                                                    />
                                                                </div>
                                                            </div>

                                                            <Separator className="my-4" />

                                                            {/* Interpretación */}
                                                            <div className="rounded-lg bg-blue-50 p-4 dark:bg-blue-950/20">
                                                                <p className="text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                                                                    {
                                                                        ratio
                                                                            .promedio_sector
                                                                            .interpretacion
                                                                    }
                                                                </p>
                                                            </div>
                                                        </>
                                                    ) : (
                                                        <div className="rounded-lg border border-dashed border-slate-300 p-6 text-center dark:border-slate-600">
                                                            <Info className="mx-auto mb-2 size-8 text-slate-400" />
                                                            <p className="text-sm text-slate-600 dark:text-slate-400">
                                                                No hay datos
                                                                suficientes de
                                                                otras empresas
                                                                para calcular el
                                                                promedio
                                                            </p>
                                                        </div>
                                                    )}
                                                </CardContent>
                                            </Card>
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
