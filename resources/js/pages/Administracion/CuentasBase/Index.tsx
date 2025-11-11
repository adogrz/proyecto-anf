
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { columns, CuentaBase } from './columns';
import { DataTable } from '@/components/ui/data-table';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { PlusCircle, Upload, Download, AlertTriangle, Loader2 } from 'lucide-react';
import { Empresa, type BreadcrumbItem } from '@/types';
import ImportCuentasBaseModal from '@/components/ImportCuentasBaseModal';
import axios from 'axios';
import { toast } from 'sonner';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

interface PlantillaCatalogo {
    id: number;
    nombre: string;
}

interface IndexProps {
    cuentasBase: CuentaBase[];
    selectedPlantilla?: PlantillaCatalogo;
    breadcrumbs?: BreadcrumbItem[];
    empresa: Empresa;
}

export default function CuentasBaseIndex({ cuentasBase, selectedPlantilla, breadcrumbs, empresa }: IndexProps) {
    const { delete: inertiaDelete, processing: isDeleting } = useForm();
    const [isImportModalOpen, setIsImportModalOpen] = useState(false);
    const [isExporting, setIsExporting] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deletingCuenta, setDeletingCuenta] = useState<CuentaBase | null>(null);

    const handleDeleteClick = (cuenta: CuentaBase) => {
        setDeletingCuenta(cuenta);
        setShowDeleteDialog(true);
    };

    const handleDeleteConfirm = () => {
        if (deletingCuenta) {
            inertiaDelete(route('empresas.cuentas-base.destroy', { empresa: empresa.id, cuentas_base: deletingCuenta.id }), {
                onSuccess: () => {
                    toast.success('Cuenta eliminada con éxito.');
                    setShowDeleteDialog(false);
                },
                onError: () => {
                    toast.error('Error al eliminar la cuenta.');
                },
            });
        }
    };

    const handleExport = async () => {
        setIsExporting(true);
        toast.info('Iniciando exportación...', {
            description: 'Preparando el archivo de cuentas base.',
        });
        try {
            const response = await axios.get(route('empresas.cuentas-base.export', { empresa: empresa.id }), {
                responseType: 'blob',
            });

            const contentDisposition = response.headers['content-disposition'];
            let filename = 'cuentas_base.xlsx';
            if (contentDisposition) {
                const filenameMatch = contentDisposition.match(/filename="?(.+)"?/);
                if (filenameMatch && filenameMatch.length > 1) {
                    filename = filenameMatch[1];
                }
            }

            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            toast.success('Exportación completada', {
                description: `El archivo ${filename} se ha descargado.`,
            });
        } catch (error) {
            console.error('Error al exportar:', error);
            toast.error('Error al exportar', {
                description: 'No se pudo generar el archivo de exportación. Inténtelo de nuevo.',
            });
        } finally {
            setIsExporting(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Administrar Cuentas Base" />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="mb-6 flex flex-wrap gap-4 justify-between items-center">
                    <div className="flex-1 min-w-0">
                        <h1 className="text-2xl font-bold truncate">
                            Cuentas Base
                            {selectedPlantilla && `: ${selectedPlantilla.nombre}`}
                        </h1>
                        <p className="text-gray-600">
                            {selectedPlantilla
                                ? `Administra las cuentas para la plantilla "${selectedPlantilla.nombre}".`
                                : 'Selecciona una plantilla para ver sus cuentas.'}
                        </p>
                    </div>
                    {selectedPlantilla && (
                        <div className="flex flex-wrap items-center gap-2">
                            <Button asChild variant="outline">
                                <Link href={route('importacion.descargarPlantilla')}>
                                    <Download className="mr-2 h-4 w-4" />
                                    Descargar Plantilla
                                </Link>
                            </Button>
                            <Button variant="outline" onClick={handleExport} disabled={isExporting}>
                                {isExporting ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <Download className="mr-2 h-4 w-4" />
                                )}
                                Exportar Datos
                            </Button>
                            <Button variant="outline" onClick={() => setIsImportModalOpen(true)}>
                                <Upload className="mr-2 h-4 w-4" />
                                Importar
                            </Button>
                            <Link href={route('empresas.cuentas-base.create', { empresa: empresa.id })}>
                                <Button>
                                    <PlusCircle className="mr-2 h-4 w-4" /> Crear Cuenta
                                </Button>
                            </Link>
                        </div>
                    )}
                </div>

                {selectedPlantilla ? (
                    <Card>
                        <CardHeader>
                            <div className="flex justify-between items-center">
                                <div>
                                    <CardTitle>Listado de Cuentas</CardTitle>
                                    <CardDescription>
                                        {`Mostrando ${cuentasBase.length} cuentas para la plantilla "${selectedPlantilla.nombre}".`}
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <DataTable
                                columns={columns({ empresa, handleDeleteClick })}
                                data={cuentasBase}
                                filterColumn="nombre"
                                filterPlaceholder="Filtrar por nombre..."
                            />
                        </CardContent>
                    </Card>
                ) : (
                    <Card className="text-center py-12">
                        <CardHeader>
                            <div className="mx-auto bg-yellow-100 rounded-full p-3 w-max">
                                <AlertTriangle className="h-8 w-8 text-yellow-600" />
                            </div>
                            <CardTitle className="mt-4">No has seleccionado una plantilla</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-gray-600 mb-4">
                                Por favor, ve a la sección de{' '}
                                <Link href={route('empresas.index')} className="text-blue-600 hover:underline">
                                    Empresas
                                </Link>{' '}
                                y selecciona una empresa para administrar sus cuentas base.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
            {selectedPlantilla && (
                <ImportCuentasBaseModal
                    plantilla={selectedPlantilla}
                    isOpen={isImportModalOpen}
                    onClose={() => setIsImportModalOpen(false)}
                    empresa={empresa}
                />
            )}
            <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Estás absolutamente seguro?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción no se puede deshacer. Esto eliminará permanentemente la cuenta base
                            <span className="font-bold"> {deletingCuenta?.nombre} </span>
                            y todos sus datos asociados.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDeleteConfirm} disabled={isDeleting}>
                            {isDeleting ? 'Eliminando...' : 'Continuar'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
