import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import {
    BarChart3,
    Building,
    FileText,
    Layers,
    TrendingUp,
    UserCircle2,
} from 'lucide-react';

// ------------------------------------------------------
// Datos de ejemplo (luego los harás dinámicos)
// ------------------------------------------------------

const stats = [
    {
        label: 'Empresas Registradas',
        value: 4,
        icon: <Building className="size-5 text-primary" />,
    },
    {
        label: 'Sectores Analizados',
        value: 2,
        icon: <Layers className="size-5 text-primary" />,
    },
    {
        label: 'Estados Financieros',
        value: 12,
        icon: <FileText className="size-5 text-primary" />,
    },
    {
        label: 'Ratios Calculados',
        value: 10,
        icon: <BarChart3 className="size-5 text-primary" />,
    },
    {
        label: 'Promedio Sectorial',
        value: 0.65,
        icon: <TrendingUp className="size-5 text-primary" />,
    },
];

const recientes = [
    {
        empresa: 'Empresa A',
        accion: 'Subió estado financiero',
        fecha: '2025-11-01',
    },
    { empresa: 'Empresa B', accion: 'Actualizó ratios', fecha: '2025-10-28' },
    { empresa: 'Empresa C', accion: 'Generó proyección', fecha: '2025-10-25' },
];

// ------------------------------------------------------
// Componente principal
// ------------------------------------------------------

export default function Dashboard() {
    return (
        <AppLayout>
            <Head title="Dashboard Financiero" />
            <div className="flex flex-col gap-6 p-4">
                {/* SECCIÓN 1: Bienvenida */}
                <Card className="border border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="text-xl font-bold">
                                Bienvenido,{' '}
                                <span className="text-primary">
                                    Usuario Demo
                                </span>{' '}
                                👋
                            </CardTitle>
                            <CardDescription>
                                Panel general del sistema de Análisis
                                Financiero.
                            </CardDescription>
                        </div>
                        <UserCircle2 className="size-10 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p>
                            Último acceso: <strong>09/11/2025</strong>
                        </p>
                        <p>
                            Rol: <strong>Administrador</strong>
                        </p>
                    </CardContent>
                </Card>

                {/* SECCIÓN 2: Métricas Clave */}
                <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                    {stats.map((stat) => (
                        <Card
                            key={stat.label}
                            className="border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        >
                            <div className="flex items-center justify-between">
                                <div className="flex flex-col">
                                    <span className="text-sm text-muted-foreground">
                                        {stat.label}
                                    </span>
                                    <span className="text-2xl font-semibold">
                                        {stat.value}
                                    </span>
                                </div>
                                {stat.icon}
                            </div>
                        </Card>
                    ))}
                </div>

                {/* SECCIÓN 5: Actividad Reciente */}
                <Card>
                    <CardHeader>
                        <CardTitle>Últimas Acciones del Sistema</CardTitle>
                        <CardDescription>
                            Actividades recientes registradas.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <table className="w-full text-sm">
                            <thead className="text-left text-muted-foreground">
                                <tr>
                                    <th className="p-2">Empresa</th>
                                    <th className="p-2">Acción</th>
                                    <th className="p-2">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recientes.map((r, idx) => (
                                    <tr
                                        key={idx}
                                        className="border-t border-sidebar-border/40"
                                    >
                                        <td className="p-2">{r.empresa}</td>
                                        <td className="p-2">{r.accion}</td>
                                        <td className="p-2">{r.fecha}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
