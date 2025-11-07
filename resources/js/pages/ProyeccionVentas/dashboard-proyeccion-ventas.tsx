'use client';

import { CreateDatoHistoricoDialog } from '@/components/proyeccion-ventas/datos-historicos/create-dato-historico-dialog';
import { getColumns } from '@/components/proyeccion-ventas/datos-historicos/datos-historicos-columns';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { DatoVentaHistorico } from '@/types/proyeccion-ventas';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { CirclePlus } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

const BREADCRUMBS: BreadcrumbItem[] = [
    { title: 'Empresas', href: '/empresas' },
    { title: 'Gestión de Datos Históricos', href: '#' },
];

interface ProyeccionesShowProps {
    datosVentaHistorico: DatoVentaHistorico[];
    empresaId: number;
    permissions: {
        canCreate: boolean;
        canEdit: boolean;
        canDelete: boolean;
    };
}

export default function ProyeccionesShow({
    datosVentaHistorico,
    empresaId,
    permissions,
}: ProyeccionesShowProps) {
    const pageTitle = 'Gestión de Datos Históricos';
    const [nextPeriodData, setNextPeriodData] = useState<{
        hasData: boolean;
        nextPeriod: { mes: number; anio: number } | null;
    }>({ hasData: false, nextPeriod: null });

    const fetchNextPeriod = useCallback(() => {
        axios
            .get(`/proyecciones/${empresaId}/next-period`)
            .then((response) => {
                setNextPeriodData(response.data);
            })
            .catch((error) => {
                console.error('Error al obtener el próximo período:', error);
            });
    }, [empresaId]);

    useEffect(() => {
        // Obtener el próximo período al cargar el componente
        fetchNextPeriod();
    }, [fetchNextPeriod]);

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
                            <CreateDatoHistoricoDialog
                                empresaId={empresaId}
                                nextPeriod={nextPeriodData.nextPeriod}
                                hasData={nextPeriodData.hasData}
                                onSuccess={fetchNextPeriod}
                            >
                                <Button className="cursor-pointer space-x-1">
                                    <CirclePlus />
                                    <span>Añadir Dato Manualmente</span>
                                </Button>
                            </CreateDatoHistoricoDialog>
                        )}
                    </div>
                </div>

                {/* Tabla de Datos de Venta Historicos */}
                <DataTable
                    columns={getColumns({
                        empresaId,
                        permissions: {
                            canEdit: permissions.canEdit,
                            canDelete: permissions.canDelete,
                        },
                        onEdit: fetchNextPeriod,
                    })}
                    data={datosVentaHistorico}
                    filterColumn="anio"
                    filterPlaceholder="Filtrar por año..."
                />
            </div>
        </AppLayout>
    );
}
