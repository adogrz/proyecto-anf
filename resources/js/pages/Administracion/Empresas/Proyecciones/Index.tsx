
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus } from 'lucide-react';

// Interfaces
interface Empresa {
    id: number;
    nombre: string;
}

interface Proyeccion {
    id: number;
    mes: number;
    monto_ventas: number;
}

type ProyeccionesAgrupadas = Record<string, Record<string, Proyeccion[]>>;

interface IndexProps {
    empresa: Empresa;
    proyecciones: ProyeccionesAgrupadas;
}

const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
const TIPO_LABELS: Record<string, string> = {
    historico: 'Histórico',
    proyectado_minimos_cuadrados: 'Proyección (Mínimos Cuadrados)',
    proyectado_porcentual: 'Proyección (Incr. Porcentual)',
    proyectado_absoluto: 'Proyección (Incr. Absoluto)',
};

export default function ProyeccionesIndex({ empresa, proyecciones }: IndexProps) {
    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
    };

    return (
        <AppLayout>
            <Head title={`Proyecciones de Ventas de ${empresa.nombre}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold">Proyecciones de Ventas de {empresa.nombre}</h1>
                    <div className="space-x-2">
                        <Button asChild variant="outline">
                            <Link href={route('empresas.show', empresa.id)}>
                                Volver a la Empresa
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={route('empresas.proyecciones.create', empresa.id)}>
                                <Plus className="mr-2 h-4 w-4" />
                                Nueva Proyección
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="space-y-8">
                    {Object.keys(proyecciones).length > 0 ? (
                        Object.keys(proyecciones).map(anio => (
                            <Card key={anio}>
                                <CardHeader>
                                    <CardTitle>Año {anio}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Tipo</TableHead>
                                                {MESES.map(mes => <TableHead key={mes} className="text-right">{mes}</TableHead>)}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {Object.keys(proyecciones[anio]).map(tipo => (
                                                <TableRow key={tipo}>
                                                    <TableCell className="font-medium">{TIPO_LABELS[tipo] || tipo}</TableCell>
                                                    {MESES.map((_, index) => {
                                                        const mesNumero = index + 1;
                                                        const proyeccionMes = proyecciones[anio][tipo].find(p => p.mes === mesNumero);
                                                        return (
                                                            <TableCell key={mesNumero} className="text-right">
                                                                {proyeccionMes ? formatCurrency(proyeccionMes.monto_ventas) : '-'}
                                                            </TableCell>
                                                        );
                                                    })}
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        ))
                    ) : (
                        <Card>
                            <CardContent className="p-6 text-center">
                                <p>No se encontraron proyecciones de ventas para esta empresa.</p>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
