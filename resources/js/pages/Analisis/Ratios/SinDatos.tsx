'use client';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, FileText } from 'lucide-react';

interface Empresa {
    id: number;
    nombre: string;
    sector?: {
        nombre: string;
    };
}

interface Props {
    empresa?: Empresa;
    anio?: number;
    mensaje: string;
}

export default function SinDatos({ empresa, anio, mensaje }: Props) {
    const BREADCRUMBS: BreadcrumbItem[] = empresa
        ? [
              { title: 'Empresas', href: '/empresas' },
              {
                  title: empresa.nombre,
                  href: `/empresas/${empresa.id}/analisis/ratios`,
              },
              { title: 'Sin Datos', href: '' },
          ]
        : [
              { title: 'Empresas', href: '/empresas' },
              { title: 'Sin Datos', href: '' },
          ];

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title="Sin Datos - Análisis de Ratios" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                {/* Header */}
                {empresa && (
                    <div className="flex flex-col gap-2">
                        <h1 className="text-3xl font-bold tracking-tight">
                            {empresa.nombre}
                        </h1>
                        {empresa.sector && (
                            <p className="text-muted-foreground">
                                Sector: {empresa.sector.nombre}
                            </p>
                        )}
                        {anio && (
                            <p className="text-sm text-muted-foreground">
                                Año: {anio}
                            </p>
                        )}
                    </div>
                )}

                {/* Empty State */}
                <Card className="border-2 border-dashed">
                    <CardContent className="flex flex-col items-center justify-center py-16">
                        <div className="mb-6 flex size-20 items-center justify-center rounded-full bg-muted">
                            <AlertCircle className="size-10 text-muted-foreground" />
                        </div>

                        <h2 className="mb-3 text-2xl font-semibold">
                            No hay datos disponibles
                        </h2>

                        <p className="mb-6 max-w-md text-center text-muted-foreground">
                            {mensaje}
                        </p>

                        <div className="flex flex-col gap-3 sm:flex-row">
                            {empresa && (
                                <>
                                    <Button asChild variant="default">
                                        <Link
                                            href={`/empresas/${empresa.id}/analisis/ratios`}
                                        >
                                            <ArrowLeft className="mr-2 size-4" />
                                            Volver al Dashboard
                                        </Link>
                                    </Button>

                                    <Button asChild variant="outline">
                                        <Link
                                            href={`/empresas/${empresa.id}/estados-financieros/create`}
                                        >
                                            <FileText className="mr-2 size-4" />
                                            Cargar Estados Financieros
                                        </Link>
                                    </Button>
                                </>
                            )}

                            {!empresa && (
                                <Button asChild variant="default">
                                    <Link href="/empresas">
                                        <ArrowLeft className="mr-2 size-4" />
                                        Volver a Empresas
                                    </Link>
                                </Button>
                            )}
                        </div>

                        {/* Información adicional */}
                        <div className="mt-8 rounded-lg bg-muted/50 p-4 text-sm text-muted-foreground">
                            <p className="font-medium">
                                Para ver análisis de ratios:
                            </p>
                            <ul className="mt-2 list-inside list-disc space-y-1">
                                <li>
                                    Primero debes cargar estados financieros
                                </li>
                                <li>Los ratios se calculan automáticamente</li>
                                <li>Necesitas al menos un año con datos</li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
