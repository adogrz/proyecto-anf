
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { columns, EstadoFinanciero } from './columns';
import { DataTable } from '@/components/ui/data-table';

// Interfaces
interface Empresa {
    id: number;
    nombre: string;
}

interface IndexProps {
    empresa: Empresa;
    estadosFinancieros: EstadoFinanciero[];
}

export default function EstadosFinancierosIndex({ empresa, estadosFinancieros }: IndexProps) {
    return (
        <AppLayout>
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

                <div className="shadow-md rounded-lg p-6">
                    <DataTable 
                        columns={columns} 
                        data={estadosFinancieros} 
                        filterColumn="anio"
                        filterPlaceholder="Filtrar por año..."
                    />
                </div>
            </div>
        </AppLayout>
    );
}
