import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { columns, EstadoFinanciero } from './columns';
import { DataTable } from '@/components/ui/data-table';
import { BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';

// Interfaces
interface Empresa {
    id: number;
    nombre: string;
}

interface IndexProps {
    empresa: Empresa;
    estadosFinancieros: EstadoFinanciero[];
    filters: {
        anio: string | null;
        tipo_estado: string | null;
    };
    availableTiposEstado: string[];
}

export default function EstadosFinancierosIndex({ empresa, estadosFinancieros, filters, availableTiposEstado }: IndexProps) {
    const BREADCRUMBS: BreadcrumbItem[] = [
        { title: 'Home', href: route('dashboard') },
        { title: 'Empresas', href: route('empresas.index') },
        { title: empresa.nombre, href: route('empresas.show', empresa.id) },
        { title: 'Estados Financieros', href: route('empresas.estados-financieros.index', empresa.id) },
    ];

    const { data, get } = useForm({
        tipo_estado: filters.tipo_estado ?? 'all', // Map null to 'all' for Select
    });

    const handleFilterChange = (value: string) => {
        // If 'all' is selected, send null to the backend
        const backendValue = value === 'all' ? null : value;
        data.tipo_estado = backendValue; // Update local form data

        get(route('empresas.estados-financieros.index', { empresa: empresa.id, tipo_estado: data.tipo_estado }), {
            preserveState: true,
            replace: true,
        });
    };

    const formatTipoEstadoDisplay = (tipo: string) => {
        return tipo.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    };

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title={`Estados Financieros de ${empresa.nombre}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold">Estados Financieros de {empresa.nombre}</h1>
                    <Button asChild variant="outline">
                        <Link href={route('empresas.index')}>
                            Volver a Empresas
                        </Link>
                    </Button>
                </div>

                <div className="mb-4 flex justify-end"> {/* Use justify-end to align to the right */}
                    <div className="w-1/3"> {/* Adjust width as needed */}
                        <Label htmlFor="filterTipoEstado">Filtrar por Tipo de Estado</Label>
                        <Select value={data.tipo_estado ?? 'all'} onValueChange={(value) => handleFilterChange(value)}>
                            <SelectTrigger id="filterTipoEstado" className="w-full">
                                <SelectValue placeholder="Seleccionar Tipo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los Tipos</SelectItem>
                                {availableTiposEstado.map((tipo) => (
                                    <SelectItem key={tipo} value={tipo}>
                                        {formatTipoEstadoDisplay(tipo)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="shadow-md rounded-lg p-6">
                    <DataTable 
                        columns={columns} 
                        data={estadosFinancieros} 
                    />
                </div>
            </div>
        </AppLayout>
    );
}