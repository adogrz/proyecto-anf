
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { columns, CuentaBase } from './columns';
import { DataTable } from '@/components/ui/data-table';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { PlusCircle, Upload, Download, AlertTriangle } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import ImportCuentasBaseModal from '@/components/ImportCuentasBaseModal';

interface PlantillaCatalogo {
    id: number;
    nombre: string;
}

interface IndexProps {
    cuentasBase: CuentaBase[];
    selectedPlantilla?: PlantillaCatalogo;
    breadcrumbs?: BreadcrumbItem[];
}

export default function CuentasBaseIndex({ cuentasBase, selectedPlantilla, breadcrumbs }: IndexProps) {
    const { delete: inertiaDelete } = useForm();
    const [selectedRows, setSelectedRows] = useState<number[]>([]);
    const [isImportModalOpen, setIsImportModalOpen] = useState(false);

    const handleBulkDelete = () => {
        if (selectedRows.length === 0) {
            alert('Selecciona al menos una cuenta base para eliminar.');
            return;
        }

        if (confirm(`¿Estás seguro de que quieres eliminar ${selectedRows.length} cuentas base seleccionadas?`)) {
            alert('Funcionalidad de eliminación masiva no implementada en el backend. IDs a eliminar: ' + selectedRows.join(', '));
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
                                <Link href={route('cuentas-base.download-template')}>
                                    <Download className="mr-2 h-4 w-4" />
                                    Descargar Plantilla
                                </Link>
                            </Button>
                            <Button asChild variant="outline" disabled={!selectedPlantilla}>
                                <Link href={selectedPlantilla ? route('cuentas-base.export', { plantilla: selectedPlantilla.id }) : '#'}>
                                    <Download className="mr-2 h-4 w-4" />
                                    Exportar Datos
                                </Link>
                            </Button>
                            <Button variant="outline" onClick={() => setIsImportModalOpen(true)}>
                                <Upload className="mr-2 h-4 w-4" />
                                Importar
                            </Button>
                            <Link href={route('cuentas-base.create', { plantilla: selectedPlantilla.id })}>
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
                                {selectedRows.length > 0 && (
                                    <Button variant="destructive" onClick={handleBulkDelete}>
                                        Eliminar ({selectedRows.length})
                                    </Button>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent>
                            <DataTable
                                columns={columns}
                                data={cuentasBase}
                                filterColumn="nombre"
                                filterPlaceholder="Filtrar por nombre..."
                                onRowSelectionChange={setSelectedRows}
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
                                <Link href={route('plantillas-catalogo.index')} className="text-blue-600 hover:underline">
                                    Plantillas de Catálogo
                                </Link>{' '}
                                para administrar las cuentas de una plantilla específica.
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
                />
            )}
        </AppLayout>
    );
}
