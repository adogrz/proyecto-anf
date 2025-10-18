
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-react';
import { columns, Empresa } from './columns';
import { DataTable } from '@/components/ui/data-table';
import { route } from 'ziggy-js';

// Definiendo los props del componente
interface IndexProps {
    empresas: Empresa[];
}

export default function EmpresasIndex({ empresas }: IndexProps) {
    return (
        <AppLayout>
            <Head title="Gestión de Empresas" />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold">Gestión de Empresas</h1>
                    <Button asChild>
                        <Link href={route('empresas.create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            Crear Empresa
                        </Link>
                    </Button>
                </div>

                <div className="shadow-md rounded-lg p-6">
                    <DataTable 
                        columns={columns} 
                        data={empresas} 
                        filterColumn="nombre"
                        filterPlaceholder="Filtrar por nombre..."
                    />
                </div>
            </div>
        </AppLayout>
    );
}
