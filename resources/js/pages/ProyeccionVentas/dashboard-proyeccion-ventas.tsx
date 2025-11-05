'use client';

import { columns } from '@/components/proyeccion-ventas/datos-historicos/datos-historicos-columns';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { DatoVentaHistorico } from '@/types/proyeccion-ventas';
import { Head } from '@inertiajs/react';
import { CirclePlus } from 'lucide-react';

const BREADCRUMBS: BreadcrumbItem[] = [
    { title: 'Empresas', href: '/empresas' },
    { title: 'Gestión de Datos Históricos', href: '#' },
];

interface ProyeccionesShowProps {
    datosVentaHistorico: DatoVentaHistorico[];
    permissions: {
        canCreate: boolean;
        canEdit: boolean;
        canDelete: boolean;
    };
}

export default function ProyeccionesShow({
    datosVentaHistorico,
    permissions,
}: ProyeccionesShowProps) {
    const pageTitle = 'Gestión de Datos Históricos';

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title={pageTitle} />
            <div className="flex h-full flex-1 flex-col gap-2 overflow-x-auto rounded-xl p-4">
                {/* Encabezado */}
                <div className="flex flex-wrap items-center justify-between space-y-2">
                    <h2 className="text-2xl font-bold tracking-tight">
                        {pageTitle}
                    </h2>
                    <div>
                        {permissions.canCreate && (
                            <Button className="cursor-pointer space-x-1">
                                <CirclePlus />
                                <span>Añadir Dato Manualmente</span>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Tabla de Datos de Venta Historicos */}
                <DataTable
                    columns={columns}
                    data={datosVentaHistorico}
                    filterColumn="anio"
                    filterPlaceholder="Filtrar por año..."
                />
            </div>
        </AppLayout>
    );
}
