
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { columns as ratiosColumns, Ratio } from './ratios-columns';
import { DataTable } from '@/components/ui/data-table';
import { Sector } from './columns';
import { Plus } from 'lucide-react';

// Definiendo los props del componente
interface ShowProps {
    sector: Sector & { ratios: Ratio[] };
}

export default function SectoresShow({ sector }: ShowProps) {
    return (
        <AppLayout>
            <Head title={`Sector: ${sector.nombre}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-2xl font-bold">{sector.nombre}</h1>
                        <p className="text-lg text-gray-600">{sector.descripcion}</p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={route('sectores.index')}>
                            Volver a Sectores
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex justify-between items-center">
                            <div>
                                <CardTitle>Ratios Estándar del Sector</CardTitle>
                                <CardDescription>Estos son los ratios estándar para el sector {sector.nombre}.</CardDescription>
                            </div>
                            <Button asChild>
                                <Link href={route('sectores.ratios.create', sector.id)}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Añadir Ratio
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <DataTable 
                            columns={ratiosColumns} 
                            data={sector.ratios} 
                            filterColumn="nombre_ratio"
                            filterPlaceholder="Filtrar por nombre..."
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
