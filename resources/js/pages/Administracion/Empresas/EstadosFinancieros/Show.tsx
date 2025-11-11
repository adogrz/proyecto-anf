
import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';
import { Badge } from '@/components/ui/badge';

// Interfaces
interface Empresa {
    id: number;
    nombre: string;
}

interface CuentaBase {
    id: number;
    nombre: string;
    codigo: string;
    naturaleza: string;
    parent_id: number | null;
}

interface CatalogoCuenta {
    id: number;
    codigo_cuenta: string;
    nombre_cuenta: string;
    cuenta_base: CuentaBase | null;
}

interface DetalleEstado {
    id: number;
    valor: number;
    catalogo_cuenta: CatalogoCuenta | null;
    root_cuenta_base_name: string;
}

interface EstadoFinanciero {
    id: number;
    anio: number;
    tipo_estado: string;
    empresa: Empresa | null;
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

    const BREADCRUMBS: BreadcrumbItem[] = [
        { title: 'Home', href: route('dashboard') },
        { title: 'Empresas', href: route('empresas.index') },
        { title: estadoFinanciero.empresa?.nombre || 'Empresa Desconocida', href: estadoFinanciero.empresa?.id ? route('empresas.show', estadoFinanciero.empresa.id) : '#' },
        { title: 'Estados Financieros', href: estadoFinanciero.empresa?.id ? route('empresas.estados-financieros.index', estadoFinanciero.empresa.id) : '#' },
        { title: `${estadoFinanciero.tipo_estado ? formatTipoEstado(estadoFinanciero.tipo_estado) : 'Tipo Desconocido'} ${estadoFinanciero.anio}`, href: estadoFinanciero.empresa?.id ? route('empresas.estados-financieros.show', { empresa: estadoFinanciero.empresa.id, estados_financiero: estadoFinanciero.id }) : '#' },
    ];

    const groupTotals = Object.keys(detallesAgrupados).reduce((acc, groupName) => {
        acc[groupName] = detallesAgrupados[groupName].reduce((sum, detalle) => sum + Number(detalle.valor), 0);
        return acc;
    }, {} as Record<string, number>);

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title={`${estadoFinanciero.tipo_estado ? formatTipoEstado(estadoFinanciero.tipo_estado) : 'Estado Financiero'} ${estadoFinanciero.anio}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-2xl font-bold">{estadoFinanciero.tipo_estado ? formatTipoEstado(estadoFinanciero.tipo_estado) : 'Estado Financiero'} - {estadoFinanciero.anio}</h1>
                        <p className="text-lg text-gray-600">
                            {estadoFinanciero.empresa ? estadoFinanciero.empresa.nombre : 'Empresa no disponible'}
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <Link href={estadoFinanciero.empresa?.id ? route('empresas.estados-financieros.index', { empresa: estadoFinanciero.empresa.id }) : '#'}>
                            Volver a Estados Financieros
                        </Link>
                    </Button>
                </div>

                <Card className="space-y-8 p-6"> {/* Single Card wrapper */}
                    {Object.keys(detallesAgrupados).map(groupName => (
                        <div key={groupName}> {/* Removed Card, now a div */}
                            <h2 className="text-xl font-semibold mb-4">{groupName}</h2> {/* h2 heading */}
                            <Table>
                                <TableHeader>
                                    <TableRow><TableHead>Cuenta</TableHead><TableHead>Naturaleza</TableHead><TableHead className="text-right">Valor</TableHead></TableRow>
                                </TableHeader>
                                <TableBody>
                                    {detallesAgrupados[groupName].map(detalle => (
                                        <TableRow key={detalle.id}><TableCell>
                                                {detalle.catalogo_cuenta?.codigo_cuenta} - {detalle.catalogo_cuenta?.nombre_cuenta || 'Cuenta Desconocida'}
                                            </TableCell><TableCell>
                                                <Badge variant={detalle.catalogo_cuenta?.cuenta_base?.naturaleza === 'DEUDORA' ? 'blue' : 'green'}>
                                                    {detalle.catalogo_cuenta?.cuenta_base?.naturaleza || 'N/A'}
                                                </Badge>
                                            </TableCell><TableCell className="text-right">
                                                <Badge variant={detalle.valor >= 0 ? 'green' : 'red'}>
                                                    {formatCurrency(detalle.valor)}
                                                </Badge>
                                            </TableCell></TableRow>
                                    ))}
                                </TableBody>
                                <TableFooter>
                                    <TableRow><TableCell className="font-bold">Total {groupName}</TableCell><TableCell></TableCell><TableCell className="text-right font-bold">{formatCurrency(groupTotals[groupName])}</TableCell></TableRow>
                                </TableFooter>
                            </Table>
                        </div>
                    ))}
                    {/* Grand Total Card - now just a div inside the main Card */}
                    {estadoFinanciero.tipo_estado === 'estado_resultados' && ( // Conditional rendering
                        <div className="pt-4 border-t mt-4"> {/* Added border-t for separation */}
                            <h2 className="text-xl font-semibold mb-4">Resumen</h2>
                            <div className="flex justify-between items-center text-xl font-bold">
                                <span>Total General</span>
                                <span>{formatCurrency(Object.values(groupTotals).reduce((sum, total) => sum + total, 0))}</span>
                            </div>
                        </div>
                    )}
                </Card> {/* End of single Card wrapper */}
            </div>
        </AppLayout>
    );
}
