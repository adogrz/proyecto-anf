import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { route } from 'ziggy-js';
import { columns, Empresa } from './columns';

// Definiendo los props del componente
interface IndexProps {
    empresas: Empresa[];
}

const BREADCRUMBS: BreadcrumbItem[] = [
    { title: 'Empresas', href: '/empresas' },
];

export default function EmpresasIndex({ empresas }: IndexProps) {
    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title="Gestión de Empresas" />
            <div className="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Gestión de Empresas</h1>
                    <Button asChild>
                        <Link href={route('empresas.create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            Crear Empresa
                        </Link>
                    </Button>
                </div>

                <div className="rounded-lg p-6 shadow-md">
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
