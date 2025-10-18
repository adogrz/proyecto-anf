import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { columns, CuentaBase } from './columns';
import { DataTable } from '@/components/ui/data-table';
import { useState } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { PlusCircle } from 'lucide-react';
import { useForm } from '@inertiajs/react';

interface PlantillaCatalogo {
    id: number;
    nombre: string;
}

interface IndexProps {
    cuentasBase: CuentaBase[];
    plantillas: PlantillaCatalogo[];
}

export default function CuentasBaseIndex({ cuentasBase, plantillas }: IndexProps) {
    const [selectedPlantilla, setSelectedPlantilla] = useState<string>('');
    const { delete: inertiaDelete } = useForm();

    const filteredCuentasBase = selectedPlantilla
        ? cuentasBase.filter(cuenta => cuenta.plantilla_catalogo_id === parseInt(selectedPlantilla))
        : cuentasBase;

    const [selectedRows, setSelectedRows] = useState<number[]>([]);

    const handleBulkDelete = () => {
        if (selectedRows.length === 0) {
            alert('Selecciona al menos una cuenta base para eliminar.');
            return;
        }

        if (confirm(`¿Estás seguro de que quieres eliminar ${selectedRows.length} cuentas base seleccionadas?`)) {
            // Assuming a bulk delete endpoint or iterating through selectedRows
            // For simplicity, let's assume a single delete endpoint for now,
            // and the user will need to implement a bulk delete endpoint if needed.
            // Or, we can iterate and send multiple delete requests.
            // For now, I'll just show an alert.
            alert('Funcionalidad de eliminación masiva no implementada en el backend. IDs a eliminar: ' + selectedRows.join(', '));
            // Example of how you might send individual delete requests:
            // selectedRows.forEach(id => {
            //     inertiaDelete(route('cuentas-base.destroy', id));
            // });
            // Or, if a bulk delete endpoint exists:
            // inertiaDelete(route('cuentas-base.bulk-destroy'), { data: { ids: selectedRows } });
        }
    };

    return (
        <AppLayout>
            <Head title="Catálogo de Cuentas Base" />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="mb-6 flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold">Catálogo de Cuentas Base</h1>
                        <p className="text-gray-600">Este es el catálogo maestro de cuentas que utiliza el sistema.</p>
                    </div>
                    <Link href={route('cuentas-base.create')}>
                        <Button>
                            <PlusCircle className="mr-2 h-4 w-4" /> Crear Nueva Cuenta Base
                        </Button>
                    </Link>
                </div>

                <div className="mb-4 flex items-center space-x-2">
                    <label htmlFor="plantilla-filter" className="text-sm font-medium">Filtrar por Plantilla:</label>
                    <Select onValueChange={setSelectedPlantilla} value={selectedPlantilla}>
                        <SelectTrigger className="w-[200px]">
                            <SelectValue placeholder="Todas las Plantillas" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={null}>Todas las Plantillas</SelectItem>
                            {plantillas.map(plantilla => (
                                <SelectItem key={plantilla.id} value={plantilla.id.toString()}>
                                    {plantilla.nombre}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {selectedRows.length > 0 && (
                        <Button variant="destructive" onClick={handleBulkDelete}>
                            Eliminar Seleccionadas ({selectedRows.length})
                        </Button>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Cuentas Base</CardTitle>
                        <CardDescription>Listado de todas las cuentas base del sistema.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable
                            columns={columns}
                            data={filteredCuentasBase}
                            filterColumn="nombre"
                            filterPlaceholder="Filtrar por nombre..."
                            onRowSelectionChange={setSelectedRows}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}