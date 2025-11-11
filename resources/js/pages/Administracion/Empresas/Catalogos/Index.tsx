
import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Plus } from 'lucide-react';
import { DataTable } from '@/components/ui/data-table';
import { type BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';
import { columns, CatalogoCuenta } from './columns';

// Interfaces
interface Empresa {
    id: number;
    nombre: string;
}

interface IndexProps {
    empresa: Empresa;
    catalogosCuentas: CatalogoCuenta[];
}

const BREADCRUMBS = (empresa: Empresa): BreadcrumbItem[] => [
    { title: 'Home', href: route('dashboard') },
    { title: 'Empresas', href: route('empresas.index') },
    { title: empresa.nombre, href: route('empresas.show', empresa.id) },
    { title: 'Catálogo de Cuentas', href: route('empresas.catalogos.index', empresa.id) },
];

export default function CatalogosCuentasIndex({ empresa, catalogosCuentas }: IndexProps) {
    return (
        <AppLayout breadcrumbs={BREADCRUMBS(empresa)}>
            <Head title={`Catálogo de Cuentas de ${empresa.nombre}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold">Catálogo de Cuentas de {empresa.nombre}</h1>
                    <Button asChild>
                        <Link href={route('empresas.catalogos.create', empresa.id)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Crear Cuenta
                        </Link>
                    </Button>
                </div>

                <div className="shadow-md rounded-lg p-6">
                    <DataTable
                        columns={columns}
                        data={catalogosCuentas}
                        filterColumn="nombre_cuenta"
                        filterPlaceholder="Filtrar por nombre de cuenta..."
                    />
                </div>
            </div>
        </AppLayout>
    );
}
