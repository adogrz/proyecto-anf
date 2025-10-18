
import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';

// Interfaces
interface Empresa {
    id: number;
    nombre: string;
}

interface CuentaBase {
    nombre: string;
}

interface CatalogoCuenta {
    nombre_cuenta: string;
    cuentaBase: CuentaBase;
}

interface DetalleEstado {
    id: number;
    valor: number;
    cuenta: CatalogoCuenta;
}

interface EstadoFinanciero {
    id: number;
    anio: number;
    tipo_estado: string;
    empresa: Empresa;
}

interface ShowProps {
    estadoFinanciero: EstadoFinanciero;
    detallesAgrupados: Record<string, DetalleEstado[]>;
}

export default function EstadosFinancierosShow({ estadoFinanciero, detallesAgrupados }: ShowProps) {
    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
    };

    const formatTipoEstado = (tipo: string) => {
        return tipo.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    };

    const groupTotals = Object.keys(detallesAgrupados).reduce((acc, groupName) => {
        acc[groupName] = detallesAgrupados[groupName].reduce((sum, detalle) => sum + Number(detalle.valor), 0);
        return acc;
    }, {} as Record<string, number>);

    return (
        <AppLayout>
            <Head title={`${formatTipoEstado(estadoFinanciero.tipo_estado)} ${estadoFinanciero.anio}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-2xl font-bold">{formatTipoEstado(estadoFinanciero.tipo_estado)} - {estadoFinanciero.anio}</h1>
                        <p className="text-lg text-gray-600">{estadoFinanciero.empresa.nombre}</p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={route('empresas.estados-financieros.index', { empresa: estadoFinanciero.empresa.id })}>
                            Volver a Estados Financieros
                        </Link>
                    </Button>
                </div>

                <div className="space-y-8">
                    {Object.keys(detallesAgrupados).map(groupName => (
                        <Card key={groupName}>
                            <CardHeader>
                                <CardTitle>{groupName}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Cuenta</TableHead>
                                            <TableHead className="text-right">Valor</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {detallesAgrupados[groupName].map(detalle => (
                                            <TableRow key={detalle.id}>
                                                <TableCell>{detalle.cuenta.nombre_cuenta}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(detalle.valor)}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                    <TableFooter>
                                        <TableRow>
                                            <TableCell className="font-bold">Total {groupName}</TableCell>
                                            <TableCell className="text-right font-bold">{formatCurrency(groupTotals[groupName])}</TableCell>
                                        </TableRow>
                                    </TableFooter>
                                </Table>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
