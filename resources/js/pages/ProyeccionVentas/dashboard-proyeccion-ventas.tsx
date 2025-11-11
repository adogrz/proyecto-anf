'use client';

import { CreateDatoHistoricoDialog } from '@/components/proyeccion-ventas/datos-historicos/create-dato-historico-dialog';
import { getColumns } from '@/components/proyeccion-ventas/datos-historicos/datos-historicos-columns';
import { ImportCSVDialog } from '@/components/proyeccion-ventas/import-csv-dialog';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { DatoVentaHistorico } from '@/types/proyeccion-ventas';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import {
    CirclePlus,
    Download,
    LineChart,
    TriangleAlert,
    Upload,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

const BREADCRUMBS: BreadcrumbItem[] = [
    { title: 'Empresas', href: '/empresas' },
    { title: 'Gestión de Datos Históricos', href: '#' },
];

interface ProyeccionesShowProps {
    datosVentaHistorico: DatoVentaHistorico[];
    empresaId: number;
    cantidadMeses: number;
    puedeGenerarProyecciones: boolean;
    permissions: {
        canCreate: boolean;
        canEdit: boolean;
        canDelete: boolean;
    };
}

export default function ProyeccionesShow({
    datosVentaHistorico,
    empresaId,
    cantidadMeses,
    puedeGenerarProyecciones,
    permissions,
}: ProyeccionesShowProps) {
    const pageTitle = 'Gestión de Datos Históricos';
    const [nextPeriodData, setNextPeriodData] = useState<{
        hasData: boolean;
        nextPeriod: { mes: number; anio: number } | null;
    }>({ hasData: false, nextPeriod: null });

    const [rows, setRows] = useState<DatoVentaHistorico[]>(datosVentaHistorico);

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
        fetchNextPeriod();
    }, [fetchNextPeriod]);

    const handleDeleted = (id: number) => {
        setRows((prev) => prev.filter((r) => r.id !== id));
        fetchNextPeriod();
    };

    const lastRowId = rows
        .slice()
        .sort((a, b) => (a.anio === b.anio ? a.mes - b.mes : a.anio - b.anio))
        .at(-1)?.id;

    const handleDescargarPlantilla = () => {
        window.location.href = '/proyecciones/plantilla/descargar';
    };

    const handleGenerarProyecciones = () => {
        router.visit(`/proyecciones/${empresaId}/generar`);
    };

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title={pageTitle} />

            <div className="flex h-full flex-1 flex-col gap-2 overflow-x-auto rounded-xl p-4">
                {/* Encabezado principal con acción destacada */}
                <div className="flex flex-col space-y-2">
                    <div className="flex flex-wrap items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight">
                            {pageTitle}
                        </h2>

                        <Button
                            onClick={handleGenerarProyecciones}
                            className="space-x-1"
                            disabled={!puedeGenerarProyecciones}
                        >
                            <LineChart className="h-4 w-4" />
                            <span>Generar proyecciones</span>
                        </Button>
                    </div>

                    {/* Acciones secundarias */}
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="secondary"
                            className="cursor-pointer space-x-1"
                            onClick={handleDescargarPlantilla}
                        >
                            <Download className="h-4 w-4" />
                            <span>Descargar plantilla CSV</span>
                        </Button>

                        {permissions.canCreate && (
                            <>
                                <ImportCSVDialog empresaId={empresaId}>
                                    <Button
                                        variant="secondary"
                                        className="cursor-pointer space-x-1"
                                    >
                                        <Upload className="h-4 w-4" />
                                        <span>Importar CSV</span>
                                    </Button>
                                </ImportCSVDialog>

                                <CreateDatoHistoricoDialog
                                    empresaId={empresaId}
                                    nextPeriod={nextPeriodData.nextPeriod}
                                    hasData={nextPeriodData.hasData}
                                    onSuccess={fetchNextPeriod}
                                >
                                    <Button
                                        variant="secondary"
                                        className="cursor-pointer space-x-1"
                                    >
                                        <CirclePlus className="h-4 w-4" />
                                        <span>Añadir Dato Manualmente</span>
                                    </Button>
                                </CreateDatoHistoricoDialog>
                            </>
                        )}
                    </div>
                </div>

                {/* Alerta de datos insuficientes */}
                {!puedeGenerarProyecciones && (
                    <div className="mt-2 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-950/50">
                        <p className="text-sm text-amber-800 dark:text-amber-200">
                            <TriangleAlert
                                className="me-3 -mt-0.5 inline-flex"
                                size={16}
                                aria-hidden="true"
                            />
                            Se requieren al menos <strong>12 meses</strong> de
                            datos históricos para generar proyecciones.
                            Actualmente tienes <strong>{cantidadMeses}</strong>{' '}
                            {cantidadMeses === 1 ? 'mes' : 'meses'} registrados.
                            {12 - cantidadMeses > 0 && (
                                <>
                                    {' '}
                                    Agrega <strong>
                                        {12 - cantidadMeses}
                                    </strong>{' '}
                                    {12 - cantidadMeses === 1 ? 'mes' : 'meses'}{' '}
                                    más.
                                </>
                            )}
                        </p>
                    </div>
                )}

                {/* Tabla de datos */}
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
