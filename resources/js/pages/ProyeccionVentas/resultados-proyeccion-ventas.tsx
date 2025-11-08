import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

export default function ProyeccionesShow() {
    const BREADCRUMBS: BreadcrumbItem[] = [
        { title: 'Empresas', href: '/empresas' },
        { title: 'Gestión de Datos Históricos', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title="Resultado de Proyección de Ventas" />
            <div className="flex h-full flex-1 flex-col gap-2 overflow-x-auto rounded-xl p-4">
                <h1 className="text-xl font-semibold">
                    Resultados de Proyección de Ventas
                </h1>
            </div>
        </AppLayout>
    );
}
