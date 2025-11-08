'use client';

import { CreateDatoHistoricoDialog } from '@/components/proyeccion-ventas/datos-historicos/create-dato-historico-dialog';
import { getColumns } from '@/components/proyeccion-ventas/datos-historicos/datos-historicos-columns';
import { ImportCSVDialog } from '@/components/proyeccion-ventas/import-csv-dialog';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { DatoVentaHistorico } from '@/types/proyeccion-ventas';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { CirclePlus, Download, Upload } from 'lucide-react';
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

    // Nuevo: estado local para la tabla
    const [rows, setRows] = useState<DatoVentaHistorico[]>(datosVentaHistorico);

    // Mantener sincronizado si Inertia rehidrata con nuevos props
    useEffect(() => {
        setRows(datosVentaHistorico);
    }, [datosVentaHistorico]);

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

    // Callback cuando se elimina una fila (actualiza tabla y próximo período)
    const handleDeleted = (id: number) => {
        setRows((prev) => prev.filter((r) => r.id !== id));
        fetchNextPeriod();
    };

    // Id del último registro (por año/mes ascendente)
    const lastRowId = rows
        .slice()
        .sort((a, b) => (a.anio === b.anio ? a.mes - b.mes : a.anio - b.anio))
        .at(-1)?.id;

    const handleDescargarPlantilla = () => {
        // Navegar a la ruta que dispara la descarga
        window.location.href = '/proyecciones/plantilla/descargar';
    };

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title={pageTitle} />
            {/* Se removió FlashMessages para evitar toasts múltiples si ya está en el layout */}
            <div className="flex h-full flex-1 flex-col gap-2 overflow-x-auto rounded-xl p-4">
                {/* Encabezado */}
                <div className="flex flex-wrap items-center justify-between space-y-2">
                    <h2 className="text-2xl font-bold tracking-tight">
                        {pageTitle}
                    </h2>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="secondary"
                            className="cursor-pointer space-x-1"
                            onClick={handleDescargarPlantilla}
                        >
                            <Download />
                            <span>Descargar plantilla CSV</span>
                        </Button>
                        {permissions.canCreate && (
                            <>
                                <ImportCSVDialog empresaId={empresaId}>
                                    <Button
                                        variant="secondary"
                                        className="cursor-pointer space-x-1"
                                    >
                                        <Upload />
                                        <span>Importar CSV</span>
                                    </Button>
                                </ImportCSVDialog>
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
                            </>
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
                        onDelete: handleDeleted,
                        lastRowId,
                    })}
                    data={rows}
                    filterColumn="anio"
                    filterPlaceholder="Filtrar por año..."
                />
            </div>
        </AppLayout>
    );
}
