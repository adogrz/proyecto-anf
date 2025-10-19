
import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Empresa, CatalogoCuenta } from '@/types';

interface IndexProps {
    empresa: Empresa;
    catalogosCuentas: CatalogoCuenta[];
}

export default function CatalogosCuentasIndex({ empresa, catalogosCuentas }: IndexProps) {
    return (
        <AppLayout>
            <Head title={`Catálogo de ${empresa.nombre}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Catálogo de Cuentas de: {empresa.nombre}</CardTitle>
                        <Button asChild>
                            <Link href={route('empresas.catalogos.create', empresa.id)}>Añadir Cuenta</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre de Cuenta</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mapeado a (Cuenta Base)</th>
                                    <th className="relative px-6 py-3"><span className="sr-only">Editar</span></th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {catalogosCuentas.map((cuenta) => (
                                    <tr key={cuenta.id}>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{cuenta.codigo_cuenta}</td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{cuenta.nombre_cuenta}</td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {cuenta.cuentaBase ? (
                                                <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    {cuenta.cuentaBase.nombre}
                                                </span>
                                            ) : (
                                                <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    No Mapeado
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link href={route('catalogos.edit', cuenta.id)} className="text-indigo-600 hover:text-indigo-900">Editar</Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
