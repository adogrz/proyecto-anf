
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-react';
import { columns, Plantilla } from './columns';
import { DataTable } from '@/components/ui/data-table';
import { type BreadcrumbItem } from '@/types';

// Definiendo los props del componente
interface IndexProps {
    plantillas: Plantilla[];
    breadcrumbs?: BreadcrumbItem[];
}

export default function PlantillasCatalogoIndex({ plantillas, breadcrumbs }: IndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Plantillas de Catálogo" />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold">Gestión de Plantillas de Catálogo</h1>
                    <Button asChild>
                        <Link href={route('plantillas-catalogo.create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            Crear Plantilla
                        </Link>
                    </Button>
                </div>

                <div className="shadow-md rounded-lg p-6">
                    <DataTable 
                        columns={columns} 
                        data={plantillas} 
                        filterColumn="nombre"
                        filterPlaceholder="Filtrar por nombre..."
                    />
                </div>
            </div>
        </AppLayout>
    );
}