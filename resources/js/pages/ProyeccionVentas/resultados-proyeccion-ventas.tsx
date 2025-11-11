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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { CartesianGrid, Line, LineChart, XAxis, YAxis } from 'recharts';

interface SerieProyeccion {
    periodoLabel: string;
    historico: number | null;
    minimos: number | null;
    absoluto: number | null;
    porcentual: number | null;
}

interface ProyeccionesResultadosProps {
    empresaId: number;
    permissions: {
        canCreate: boolean;
        canEdit: boolean;
        canDelete: boolean;
    };
    serie: SerieProyeccion[];
    datosHistoricos: SerieProyeccion[];
    datosProyecciones: SerieProyeccion[];
}

export default function ProyeccionesResultados({
    empresaId,
    serie,
    datosHistoricos,
    datosProyecciones,
}: ProyeccionesResultadosProps) {
    const BREADCRUMBS: BreadcrumbItem[] = [
        { title: 'Empresas', href: '/empresas' },
        {
            title: 'Gestión de Datos Históricos',
            href: `/proyecciones/${empresaId}`,
        },
        { title: 'Resultados de Proyección', href: '#' },
    ];

    // Configuración del chart para shadcn/ui
    const chartConfig = {
        historico: {
            label: 'Datos Históricos',
            color: 'var(--chart-1)',
        },
        minimos: {
            label: 'Proy. Mínimos Cuadrados',
            color: 'var(--chart-2)',
        },
        absoluto: {
            label: 'Proy. Inc. Absoluto',
            color: 'var(--chart-3)',
        },
        porcentual: {
            label: 'Proy. Inc. Porcentual',
            color: 'var(--chart-4)',
        },
    } satisfies ChartConfig;

    const formatCurrency = (value: number | null) => {
        if (value === null) return 'N/A';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value);
    };

    const formatCurrencyCompact = (value: number) => {
        if (value >= 1000000) {
            return `$${(value / 1000000).toFixed(1)}M`;
        }
        if (value >= 1000) {
            return `$${(value / 1000).toFixed(0)}k`;
        }
        return `$${value.toFixed(0)}`;
    };

    const handleVolver = () => {
        router.visit(`/proyecciones/${empresaId}`);
    };

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title="Resultado de Proyección de Ventas" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 sm:p-6 lg:p-8">
                {/* Header con botón de regreso */}
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold tracking-tight">
                        Resultados de Proyección de Ventas
                    </h1>
                    <Button variant="secondary" onClick={handleVolver}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Volver a Datos Históricos
                    </Button>
                </div>

                {/* Gráfico de líneas */}
                <Card>
                    <CardHeader>
                        <CardTitle>Gráfico de Proyecciones</CardTitle>
                        <CardDescription>
                            Comparativa de datos históricos y proyecciones
                            futuras
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ChartContainer
                            config={chartConfig}
                            className="min-h-[400px] w-full"
                        >
                            <LineChart
                                accessibilityLayer
                                data={serie}
                                margin={{
                                    left: 12,
                                    right: 12,
                                }}
                            >
                                <CartesianGrid vertical={true} />
                                <XAxis
                                    dataKey="periodoLabel"
                                    tick={{ fontSize: 12 }}
                                    angle={-45}
                                    textAnchor="end"
                                    height={70}
                                    interval="preserveStartEnd"
                                />
                                <YAxis
                                    tickFormatter={formatCurrencyCompact}
                                    tick={{ fontSize: 12 }}
                                />
                                <ChartTooltip
                                    content={<ChartTooltipContent />}
                                />
                                <ChartLegend
                                    // eslint-disable-next-line @typescript-eslint/no-explicit-any
                                    content={(props: any) => (
                                        <ChartLegendContent {...props} />
                                    )}
                                />

                                {/* Línea de Datos Históricos */}
                                <Line
                                    type="monotone"
                                    dataKey="historico"
                                    stroke="var(--color-historico)"
                                    strokeWidth={2.5}
                                    dot={{
                                        fill: 'var(--color-historico)',
                                        r: 3,
                                    }}
                                    activeDot={{ r: 6 }}
                                />

                                {/* Línea de Proyección Mínimos Cuadrados */}
                                <Line
                                    type="monotone"
                                    dataKey="minimos"
                                    stroke="var(--color-minimos)"
                                    strokeWidth={2}
                                    strokeDasharray="5 3"
                                    dot={{ fill: 'var(--color-minimos)', r: 3 }}
                                    activeDot={{ r: 5 }}
                                />

                                {/* Línea de Proyección Incremento Absoluto */}
                                <Line
                                    type="monotone"
                                    dataKey="absoluto"
                                    stroke="var(--color-absoluto)"
                                    strokeWidth={2}
                                    strokeDasharray="5 5"
                                    dot={{
                                        fill: 'var(--color-absoluto)',
                                        r: 3,
                                    }}
                                    activeDot={{ r: 5 }}
                                />

                                {/* Línea de Proyección Incremento Porcentual */}
                                <Line
                                    type="monotone"
                                    dataKey="porcentual"
                                    stroke="var(--color-porcentual)"
                                    strokeWidth={2}
                                    strokeDasharray="6 4"
                                    dot={{
                                        fill: 'var(--color-porcentual)',
                                        r: 3,
                                    }}
                                    activeDot={{ r: 5 }}
                                />
                            </LineChart>
                        </ChartContainer>
                    </CardContent>
                </Card>

                {/* Tabla de datos */}
                <div>
                    <h2 className="text-lg font-semibold">
                        Tabla de Datos Detallados
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Proyecciones futuras seguidas de datos históricos (más
                        reciente primero)
                    </p>
                </div>
                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="font-bold">
                                    Período
                                </TableHead>
                                <TableHead className="text-right font-bold">
                                    Venta Histórica
                                </TableHead>
                                <TableHead className="text-right font-bold">
                                    Proy. Mínimos Cuadrados
                                </TableHead>
                                <TableHead className="text-right font-bold">
                                    Proy. Inc. Absoluto
                                </TableHead>
                                <TableHead className="text-right font-bold">
                                    Proy. Inc. Porcentual
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {/* Proyecciones PRIMERO - con fondo diferente */}
                            {datosProyecciones.map((fila, index) => (
                                <TableRow
                                    key={`proy-${index}`}
                                    className="bg-muted/40 transition-colors hover:bg-muted/60"
                                >
                                    <TableCell className="font-medium">
                                        {fila.periodoLabel}
                                    </TableCell>
                                    <TableCell className="text-right text-muted-foreground">
                                        —
                                    </TableCell>
                                    <TableCell className="text-right font-medium text-emerald-500 dark:text-emerald-400">
                                        {formatCurrency(fila.minimos)}
                                    </TableCell>
                                    <TableCell className="text-right font-medium text-amber-500 dark:text-amber-400">
                                        {formatCurrency(fila.absoluto)}
                                    </TableCell>
                                    <TableCell className="text-right font-medium text-red-500 dark:text-red-400">
                                        {formatCurrency(fila.porcentual)}
                                    </TableCell>
                                </TableRow>
                            ))}

                            {/* Datos Históricos DESPUÉS - ordenados inversamente (más reciente primero) */}
                            {[...datosHistoricos]
                                .reverse()
                                .map((fila, index) => (
                                    <TableRow
                                        key={`hist-${index}`}
                                        className="bg-background"
                                    >
                                        <TableCell className="font-medium">
                                            {fila.periodoLabel}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(fila.historico)}
                                        </TableCell>
                                        <TableCell className="text-right text-muted-foreground">
                                            —
                                        </TableCell>
                                        <TableCell className="text-right text-muted-foreground">
                                            —
                                        </TableCell>
                                        <TableCell className="text-right text-muted-foreground">
                                            —
                                        </TableCell>
                                    </TableRow>
                                ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}
