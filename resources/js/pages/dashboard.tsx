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
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    ArrowUpRight,
    BarChart3,
    Building,
    Clock,
    FileText,
    Layers,
    TrendingUp,
    Users,
} from 'lucide-react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';

// ------------------------------------------------------
// Types
// ------------------------------------------------------
interface User {
    name: string;
    email: string;
    ultimo_acceso: string;
    roles: string[];
}

interface Stats {
    empresas_total: number;
    sectores_total: number;
    estados_financieros_total: number;
    ratios_calculados_total: number;
    usuarios_total: number;
}

interface EmpresaPorSector {
    sector: string;
    cantidad: number;
}

interface ActividadReciente {
    empresa: string;
    accion: string;
    fecha: string;
    fecha_relativa: string;
}

interface TopEmpresa {
    id: number;
    nombre: string;
    estados_count: number;
}

interface DashboardProps {
    user: User;
    stats: Stats;
    empresas_por_sector: EmpresaPorSector[];
    actividad_reciente: ActividadReciente[];
    top_empresas: TopEmpresa[];
}

// ------------------------------------------------------
// Componente principal
// ------------------------------------------------------

export default function Dashboard({
    user,
    stats,
    empresas_por_sector,
    actividad_reciente,
    top_empresas,
}: DashboardProps) {
    const statsCards = [
        {
            label: 'Empresas Registradas',
            value: stats.empresas_total,
            icon: Building,
            color: 'text-blue-600 dark:text-blue-400',
            bgColor: 'bg-blue-100 dark:bg-blue-950/50',
        },
        {
            label: 'Sectores Activos',
            value: stats.sectores_total,
            icon: Layers,
            color: 'text-purple-600 dark:text-purple-400',
            bgColor: 'bg-purple-100 dark:bg-purple-950/50',
        },
        {
            label: 'Estados Financieros',
            value: stats.estados_financieros_total,
            icon: FileText,
            color: 'text-green-600 dark:text-green-400',
            bgColor: 'bg-green-100 dark:bg-green-950/50',
        },
        {
            label: 'Ratios Calculados',
            value: stats.ratios_calculados_total,
            icon: BarChart3,
            color: 'text-orange-600 dark:text-orange-400',
            bgColor: 'bg-orange-100 dark:bg-orange-950/50',
        },
        {
            label: 'Usuarios Activos',
            value: stats.usuarios_total,
            icon: Users,
            color: 'text-pink-600 dark:text-pink-400',
            bgColor: 'bg-pink-100 dark:bg-pink-950/50',
        },
    ];

    const chartConfig = {
        cantidad: {
            label: 'Empresas',
            color: 'var(--chart-1)',
        },
    } satisfies ChartConfig;

    return (
        <AppLayout>
            <Head title="Dashboard Financiero" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                {/* SECCIÓN 1: Bienvenida */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div className="space-y-1.5">
                                <CardTitle className="text-2xl font-bold">
                                    ¡Bienvenido de nuevo,{' '}
                                    <span className="text-primary">
                                        {user.name}
                                    </span>
                                    ! 👋
                                </CardTitle>
                                <CardDescription className="text-base">
                                    Panel de control del sistema de Análisis
                                    Financiero
                                </CardDescription>
                            </div>
                            <div className="flex items-center gap-2 rounded-lg border bg-muted/50 p-3">
                                <Activity className="size-5 text-primary" />
                                <div className="text-sm">
                                    <p className="font-medium">
                                        {user.roles[0] || 'Usuario'}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {user.email}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Clock className="size-4" />
                            <span>Último acceso: {user.ultimo_acceso}</span>
                        </div>
                    </CardContent>
                </Card>

                {/* SECCIÓN 2: Métricas Clave con diseño mejorado */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    {statsCards.map((stat) => {
                        const Icon = stat.icon;
                        return (
                            <Card
                                key={stat.label}
                                className="group relative overflow-hidden transition-all hover:shadow-lg hover:shadow-primary/10"
                            >
                                <CardContent className="p-6">
                                    <div className="flex items-start justify-between">
                                        <div className="flex flex-col gap-2">
                                            <span className="text-sm font-medium text-muted-foreground">
                                                {stat.label}
                                            </span>
                                            <span className="text-3xl font-bold tracking-tight">
                                                {stat.value.toLocaleString()}
                                            </span>
                                        </div>
                                        <div
                                            className={`rounded-lg p-2.5 ${stat.bgColor} transition-transform group-hover:scale-110`}
                                        >
                                            <Icon
                                                className={`size-5 ${stat.color}`}
                                            />
                                        </div>
                                    </div>
                                    <div className="mt-3 flex items-center gap-1 text-xs text-muted-foreground">
                                        <TrendingUp className="size-3" />
                                        <span>Total registrado</span>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                {/* SECCIÓN 3: Gráficos */}
                <div className="grid gap-6">
                    {/* Empresas por Sector */}
                    {empresas_por_sector.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Layers className="size-5 text-primary" />
                                    Empresas por Sector
                                </CardTitle>
                                <CardDescription>
                                    Distribución de empresas según sector
                                    económico
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ChartContainer
                                    config={chartConfig}
                                    className="h-[300px] w-full"
                                >
                                    <BarChart data={empresas_por_sector}>
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            className="stroke-muted"
                                        />
                                        <XAxis
                                            dataKey="sector"
                                            className="text-xs"
                                            tickLine={false}
                                            axisLine={false}
                                        />
                                        <YAxis
                                            className="text-xs"
                                            tickLine={false}
                                            axisLine={false}
                                        />
                                        <ChartTooltip
                                            content={<ChartTooltipContent />}
                                        />
                                        <Bar
                                            dataKey="cantidad"
                                            fill="var(--color-cantidad)"
                                            radius={[8, 8, 0, 0]}
                                        />
                                    </BarChart>
                                </ChartContainer>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* SECCIÓN 4: Tablas de información */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Top Empresas */}
                    {top_empresas.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Building className="size-5 text-primary" />
                                    Top Empresas
                                </CardTitle>
                                <CardDescription>
                                    Empresas con más estados financieros
                                    registrados
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {top_empresas.map((empresa, idx) => (
                                        <Link
                                            key={empresa.id}
                                            href={route(
                                                'empresas.show',
                                                empresa.id,
                                            )}
                                            className="flex items-center justify-between rounded-lg border p-3 transition-colors hover:border-primary/50 hover:bg-muted/50"
                                        >
                                            <div className="flex items-center gap-3">
                                                <div className="flex size-8 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                                                    {idx + 1}
                                                </div>
                                                <span className="font-medium">
                                                    {empresa.nombre}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className="rounded-full bg-primary/10 px-3 py-1 text-sm font-semibold text-primary">
                                                    {empresa.estados_count}
                                                </span>
                                                <ArrowUpRight className="size-4 text-muted-foreground" />
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Actividad Reciente */}
                    {actividad_reciente.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Activity className="size-5 text-primary" />
                                    Actividad Reciente
                                </CardTitle>
                                <CardDescription>
                                    Últimas acciones en el sistema
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {actividad_reciente.map(
                                        (actividad, idx) => (
                                            <div
                                                key={idx}
                                                className="flex items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                            >
                                                <div className="mt-0.5 rounded-full bg-primary/10 p-1.5">
                                                    <FileText className="size-3.5 text-primary" />
                                                </div>
                                                <div className="flex-1 space-y-1">
                                                    <p className="text-sm font-medium">
                                                        {actividad.empresa}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {actividad.accion}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {
                                                            actividad.fecha_relativa
                                                        }
                                                    </p>
                                                </div>
                                            </div>
                                        ),
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Mensaje cuando no hay datos */}
                {stats.empresas_total === 0 && (
                    <Card className="border-dashed">
                        <CardContent className="flex flex-col items-center justify-center py-12 text-center">
                            <Building className="mb-4 size-12 text-muted-foreground/50" />
                            <h3 className="mb-2 text-lg font-semibold">
                                No hay datos disponibles
                            </h3>
                            <p className="text-sm text-muted-foreground">
                                Comienza agregando empresas y estados
                                financieros para ver las estadísticas.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
