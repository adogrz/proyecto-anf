


import React from 'react';

import { Head } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';

import CuentaNode, { type CuentaBaseNode } from '@/components/CuentaNode';

import { type BreadcrumbItem } from '@/types';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

import { Separator } from '@/components/ui/separator';



// Definiendo la interfaz para la plantilla y el árbol de cuentas

interface Plantilla {

    id: number;

    nombre: string;

    descripcion: string;

    cuentas_base_count?: number;

    empresas_count?: number;

}



interface ShowProps {

    plantilla: Plantilla;

    cuentas_base_tree: CuentaBaseNode[];

    breadcrumbs?: BreadcrumbItem[];

}



export default function PlantillasCatalogoShow({ plantilla, cuentas_base_tree = [], breadcrumbs }: ShowProps) {

    return (

        <AppLayout breadcrumbs={breadcrumbs}>

            <Head title={`Ver Plantilla: ${plantilla.nombre}`} />

            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">

                <div className="mb-6">

                    <h1 className="text-2xl font-bold">Detalles de la Plantilla: {plantilla.nombre}</h1>

                    <p className="text-gray-600 mt-1">{plantilla.descripcion}</p>

                </div>



                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

                    <Card>

                        <CardHeader>

                            <CardTitle>Resumen de Cuentas Base</CardTitle>

                            <CardDescription>Información general sobre las cuentas asociadas a esta plantilla.</CardDescription>

                        </CardHeader>

                        <CardContent>

                            <div className="flex justify-between items-center py-2 border-b">

                                <span className="font-medium">Total de Cuentas Base:</span>

                                <span>{plantilla.cuentas_base_count ?? 0}</span>

                            </div>

                            <div className="flex justify-between items-center py-2">

                                <span className="font-medium">Cuentas de Agrupación:</span>

                                <span>{cuentas_base_tree.filter(c => c.tipo_cuenta === 'AGRUPACION').length}</span>

                            </div>

                            <div className="flex justify-between items-center py-2">

                                <span className="font-medium">Cuentas de Detalle:</span>

                                <span>{cuentas_base_tree.filter(c => c.tipo_cuenta === 'DETALLE').length}</span>

                            </div>

                        </CardContent>

                    </Card>



                    <Card>

                        <CardHeader>

                            <CardTitle>Uso de la Plantilla</CardTitle>

                            <CardDescription>Indica cuántas empresas utilizan esta plantilla.</CardDescription>

                        </CardHeader>

                        <CardContent>

                            <div className="flex justify-between items-center py-2">

                                <span className="font-medium">Empresas Asociadas:</span>

                                <span>{plantilla.empresas_count ?? 0}</span>

                            </div>

                            {plantilla.empresas_count && plantilla.empresas_count > 0 && (

                                <p className="text-sm text-gray-500 mt-2">

                                    Esta plantilla está siendo utilizada por {plantilla.empresas_count} empresa(s).

                                </p>

                            )}

                        </CardContent>

                    </Card>

                </div>

                

                <Card className="shadow-md rounded-lg p-6">

                    <CardHeader>

                        <CardTitle>Estructura de Cuentas</CardTitle>

                        <CardDescription>Visualización jerárquica de las cuentas base de esta plantilla.</CardDescription>

                    </CardHeader>

                    <CardContent>

                        <div className="mt-4">

                            {cuentas_base_tree.length > 0 ? (

                                cuentas_base_tree.map((rootNode) => (

                                    <CuentaNode key={rootNode.id} node={rootNode} level={0} />

                                ))

                            ) : (

                                <p className="text-center text-gray-500">Esta plantilla aún no tiene cuentas base asociadas.</p>

                            )}

                        </div>

                    </CardContent>

                </Card>

            </div>

        </AppLayout>

    );

}
