import { Button } from '@/components/ui/button';
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
import {
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

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
                <h2 className="mb-4 text-lg font-semibold">
                    Gráfico de Proyecciones
                </h2>
                <ResponsiveContainer width="100%" height={400}>
                    <LineChart data={serie}>
                        <CartesianGrid strokeDasharray="3 3" />
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
                        <Tooltip
                            formatter={(value: unknown) => {
                                const numValue =
                                    typeof value === 'number' ? value : null;
                                return formatCurrency(numValue);
                            }}
                            labelStyle={{ fontWeight: 'bold' }}
                            contentStyle={{
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                border: '1px solid #ccc',
                                borderRadius: '8px',
                                padding: '10px',
                            }}
                        />
                        <Legend
                            wrapperStyle={{ paddingTop: '20px' }}
                            iconType="line"
                        />
                        <Line
                            type="monotone"
                            dataKey="historico"
                            stroke="#1d4ed8"
                            name="Datos Históricos"
                            strokeWidth={2.5}
                            dot={{ r: 4, fill: '#1d4ed8' }}
                            activeDot={{ r: 6 }}
                        />

                        <Line
                            type="monotone"
                            dataKey="minimos"
                            stroke="#10b981"
                            name="Proy. Mínimos Cuadrados"
                            strokeWidth={2}
                            strokeDasharray="5 3"
                            dot={{ r: 3, fill: '#10b981' }}
                            activeDot={{ r: 5 }}
                        />

                        <Line
                            type="monotone"
                            dataKey="absoluto"
                            stroke="#f59e0b"
                            name="Proy. Incremento Absoluto"
                            strokeWidth={2}
                            strokeDasharray="5 5"
                            dot={{ r: 3, fill: '#f59e0b' }}
                            activeDot={{ r: 5 }}
                        />

                        <Line
                            type="monotone"
                            dataKey="porcentual"
                            stroke="#ef4444"
                            name="Proy. Incremento Porcentual"
                            strokeWidth={2}
                            strokeDasharray="6 4"
                            dot={{ r: 3, fill: '#ef4444' }}
                            activeDot={{ r: 5 }}
                        />
                    </LineChart>
                </ResponsiveContainer>

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
