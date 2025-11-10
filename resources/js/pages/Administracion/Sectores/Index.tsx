import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-react';
import { columns, Sector } from './columns';
import { DataTable } from '@/components/ui/data-table';

// Definiendo los props del componente
interface IndexProps {
    sectores: Sector[];
}

export default function SectoresIndex({ sectores }: IndexProps) {
    console.log(sectores);
    return (
        <AppLayout>
            <Head title="Gestión de Sectores" />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold">Gestión de Sectores</h1>
                    <Button asChild>
                        <Link href={route('sectores.create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            Crear Sector
                        </Link>
                    </Button>
                </div>

                <div className="shadow-md rounded-lg p-6">
                    <DataTable 
                        columns={columns} 
                        data={sectores} 
                        filterColumn="nombre"
                        filterPlaceholder="Filtrar por nombre..."
                    />
                </div>
            </div>
        </AppLayout>
    );
}