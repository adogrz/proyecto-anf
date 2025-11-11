import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { route } from 'ziggy-js';

// Interfaces
interface Sector {
    id: number;
    nombre: string;
    descripcion?: string;
}

interface PlantillaCatalogo {
    id: number;
    nombre: string;
    descripcion?: string;
}

interface CatalogoCuenta {
    id: number;
    codigo_cuenta: string;
    nombre_cuenta: string;
}

interface EstadoFinanciero {
    id: number;
    anio: number;
    tipo_estado: string;
}

interface Empresa {
    id: number;
    nombre: string;
    sector: Sector;
    plantilla_catalogo: PlantillaCatalogo | null;
    catalogoCuentas?: CatalogoCuenta[];
    estadosFinancieros?: EstadoFinanciero[];
}

interface ShowProps {
    empresa: Empresa;
}

export default function EmpresasShow({ empresa }: ShowProps) {
    const BREADCRUMBS: BreadcrumbItem[] = [
        { title: 'Empresas', href: '/empresas' },
        { title: empresa.nombre, href: '' },
    ];

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title={`Empresa: ${empresa.nombre}`} />
            <div className="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{empresa.nombre}</h1>
                        <p className="text-lg text-gray-600">
                            Detalles de la empresa
                        </p>
                    </div>
                    <div className="space-x-2">
                        <Button asChild variant="outline">
                            <Link href={route('empresas.index')}>
                                Volver a Empresas
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={route('empresas.edit', empresa.id)}>
                                Editar Empresa
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información General</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <label className="text-sm font-medium text-gray-600">
                                    Nombre:
                                </label>
                                <p className="text-lg">{empresa.nombre}</p>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-600">
                                    Sector:
                                </label>
                                <p className="text-lg">
                                    {empresa.sector.nombre}
                                </p>
                                {empresa.sector.descripcion && (
                                    <p className="text-sm text-gray-500">
                                        {empresa.sector.descripcion}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-600">
                                    Plantilla de Catálogo:
                                </label>
                                {empresa.plantilla_catalogo ? (
                                    <>
                                        <p className="text-lg">
                                            {empresa.plantilla_catalogo.nombre}
                                        </p>
                                        {empresa.plantilla_catalogo
                                            .descripcion && (
                                            <p className="text-sm text-gray-500">
                                                {
                                                    empresa.plantilla_catalogo
                                                        .descripcion
                                                }
                                            </p>
                                        )}
                                    </>
                                ) : (
                                    <p className="text-lg text-gray-500">
                                        No asignada
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Estadísticas</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex justify-between">
                                <span className="text-sm font-medium text-gray-600">
                                    Cuentas en catálogo:
                                </span>
                                <Badge variant="secondary">
                                    {empresa.catalogoCuentas?.length || 0}
                                </Badge>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-sm font-medium text-gray-600">
                                    Estados financieros:
                                </span>
                                <Badge variant="secondary">
                                    {empresa.estadosFinancieros?.length || 0}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">
                                Catálogo de Cuentas
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-4 text-sm text-gray-600">
                                Gestiona el catálogo de cuentas de la empresa
                            </p>
                            <Button asChild className="w-full">
                                <Link
                                    href={route(
                                        'empresas.catalogos.index',
                                        empresa.id,
                                    )}
                                >
                                    Ver Catálogo
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">
                                Estados Financieros
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-4 text-sm text-gray-600">
                                Consulta y gestiona los estados financieros
                            </p>
                            <Button asChild className="w-full">
                                <Link
                                    href={route(
                                        'empresas.estados-financieros.index',
                                        empresa.id,
                                    )}
                                >
                                    Ver Estados
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">
                                Análisis de Ratios
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-4 text-sm text-gray-600">
                                Dashboard de análisis de ratios financieros
                            </p>
                            <Button asChild className="w-full">
                                <Link
                                    href={route(
                                        'empresas.analisis.ratios.dashboard',
                                        empresa.id,
                                    )}
                                >
                                    Ver Análisis
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">
                                Proyección de Ventas
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-4 text-sm text-gray-600">
                                Dashboard de proyecciones de ventas
                            </p>
                            <Button asChild className="w-full">
                                <Link
                                    href={route(
                                        'dashboard.proyecciones',
                                        empresa.id,
                                    )}
                                >
                                    Ver Proyecciones
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
