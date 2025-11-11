
import React, { useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/input-error';
import { BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';

// Interfaces
interface Sector {
    id: number;
    nombre: string;
}

interface Plantilla {
    id: number;
    nombre: string;
}

interface Empresa {
    id: number;
    nombre: string;
    sector_id: number;
    plantilla_catalogo_id: number;
}

interface EditProps {
    empresa: Empresa;
    sectores: Sector[];
    plantillas: Plantilla[];
}

export default function EmpresasEdit({ empresa, sectores, plantillas }: EditProps) {
    const BREADCRUMBS: BreadcrumbItem[] = [
        { title: 'Home', href: route('dashboard') },
        { title: 'Empresas', href: route('empresas.index') },
        { title: empresa.nombre, href: route('empresas.show', empresa.id) },
        { title: 'Editar', href: route('empresas.edit', empresa.id) },
    ];

    const { data, setData, put, processing, errors } = useForm({
        nombre: empresa.nombre || '',
        sector_id: String(empresa.sector_id) || '',
        plantilla_catalogo_id: String(empresa.plantilla_catalogo_id) || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('empresas.update', empresa.id));
    };

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title={`Editar Empresa: ${empresa.nombre}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <Card className="max-w-2xl mx-auto">
                    <CardHeader>
                        <CardTitle>Editar Empresa</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="nombre">Nombre de la Empresa</Label>
                                <Input
                                    id="nombre"
                                    value={data.nombre}
                                    onChange={(e) => setData('nombre', e.target.value)}
                                    autoFocus
                                />
                                <InputError message={errors.nombre} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="sector_id">Sector</Label>
                                <Select onValueChange={(value) => setData('sector_id', value)} value={data.sector_id}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccione un sector" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {sectores.map((sector) => (
                                            <SelectItem key={sector.id} value={String(sector.id)}>
                                                {sector.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.sector_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="plantilla_catalogo_id">Plantilla de Catálogo</Label>
                                <Select onValueChange={(value) => setData('plantilla_catalogo_id', value)} value={data.plantilla_catalogo_id}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccione una plantilla" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {plantillas.map((plantilla) => (
                                            <SelectItem key={plantilla.id} value={String(plantilla.id)}>
                                                {plantilla.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.plantilla_catalogo_id} />
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
