import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { Sector } from './columns';

// interface Sector {
//     id: number;
//     nombre: string;
//     descripcion?: string;
// }

// Definiendo los props del componente
interface ShowProps {
    sector: Sector;
}

export default function SectoresShow({ sector }: ShowProps) {
    console.log(sector);
    return (
        <AppLayout>
            <Head title={`Sector: ${sector.nombre}`} />
            <div className="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-white">{sector.nombre}</h1>
                        <p className="text-lg text-white">
                            {sector.descripcion}
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={route('sectores.ratios.edit', sector.id)}>
                            Ver ratios del sector
                        </Link>
                    </Button>
                    <Button asChild variant="outline">
                        <Link href={route('sectores.index')}>
                            Volver a Sectores
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Información del Sector</CardTitle>
                        <CardDescription>
                            Detalles del sector {sector.nombre}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl className="space-y-4">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Nombre
                                </dt>
                                <dd className="mt-1 text-sm text-white">
                                    {sector.nombre}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">
                                    Descripción
                                </dt>
                                <dd className="mt-1 text-sm text-white">
                                    {sector.descripcion || 'Sin descripción'}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
