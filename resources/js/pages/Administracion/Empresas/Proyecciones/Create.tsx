
import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';

// Interfaces
interface Empresa {
    id: number;
    nombre: string;
}

interface CreateProps {
    empresa: Empresa;
}

const MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

export default function ProyeccionesCreate({ empresa }: CreateProps) {
    const initialData = {
        anio: new Date().getFullYear().toString(),
        ventas: Array(12).fill(''),
    };
    const { data, setData, post, processing, errors } = useForm(initialData);

    const handleVentasChange = (index: number, value: string) => {
        const newVentas = [...data.ventas];
        newVentas[index] = value;
        setData('ventas', newVentas);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('empresas.proyecciones.store', empresa.id));
    };

    return (
        <AppLayout>
            <Head title={`Nueva Proyección para ${empresa.nombre}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <Card className="max-w-4xl mx-auto">
                    <CardHeader>
                        <CardTitle>Nueva Proyección de Ventas</CardTitle>
                        <CardDescription>Introduce las ventas históricas de los últimos 12 meses para el año {data.anio}.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="anio">Año de los Datos Históricos</Label>
                                <Input id="anio" type="number" value={data.anio} onChange={e => setData('anio', e.target.value)} className="max-w-xs" />
                                <InputError message={errors.anio} />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                {MESES.map((mes, index) => (
                                    <div key={mes} className="space-y-2">
                                        <Label htmlFor={`venta-${index}`}>{mes}</Label>
                                        <Input 
                                            id={`venta-${index}`}
                                            type="number"
                                            step="0.01"
                                            value={data.ventas[index]}
                                            onChange={e => handleVentasChange(index, e.target.value)}
                                        />
                                        {/* @ts-ignore */}
                                        <InputError message={errors[`ventas.${index}`]} />
                                    </div>
                                ))}
                            </div>

                            <div className="flex justify-end space-x-4">
                                <Button asChild variant="outline">
                                    <Link href={route('empresas.proyecciones.index', empresa.id)}>Cancelar</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Generando...' : 'Generar Proyección'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
