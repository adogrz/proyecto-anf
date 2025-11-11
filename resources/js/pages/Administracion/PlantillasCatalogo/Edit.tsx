
import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/input-error';
import { type BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';

interface Plantilla {
    id: number;
    nombre: string;
    descripcion: string;
}

interface EditProps {
    plantilla: Plantilla;
    breadcrumbs?: BreadcrumbItem[];
}

export default function PlantillasCatalogoEdit({ plantilla }: EditProps) {
    const BREADCRUMBS: BreadcrumbItem[] = [
        { title: 'Home', href: route('dashboard') },
        { title: 'Plantillas de Catálogo', href: route('plantillas-catalogo.index') },
        { title: plantilla.nombre, href: route('plantillas-catalogo.edit', plantilla.id) },
        { title: 'Editar', href: route('plantillas-catalogo.edit', plantilla.id) },
    ];

    const { data, setData, put, processing, errors } = useForm({
        nombre: plantilla.nombre || '',
        descripcion: plantilla.descripcion || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('plantillas-catalogo.update', plantilla.id));
    };

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title={`Editar Plantilla: ${plantilla.nombre}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <Card className="max-w-2xl mx-auto">
                    <CardHeader>
                        <CardTitle>Editar Plantilla de Catálogo</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="nombre">Nombre</Label>
                                <Input
                                    id="nombre"
                                    value={data.nombre}
                                    onChange={(e) => setData('nombre', e.target.value)}
                                    autoFocus
                                />
                                <InputError message={errors.nombre} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="descripcion">Descripción</Label>
                                <Textarea
                                    id="descripcion"
                                    value={data.descripcion}
                                    onChange={(e) => setData('descripcion', e.target.value)}
                                />
                                <InputError message={errors.descripcion} />
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Guardando...' : 'Guardar Cambios'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}