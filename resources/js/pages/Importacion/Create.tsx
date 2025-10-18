import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Terminal } from 'lucide-react';

// Interfaces
interface Empresa {
    id: number;
    nombre: string;
}

interface CreateProps {
    empresas: Empresa[];
    validation_errors?: string[];
}

export default function ImportacionCreate({ empresas, validation_errors = [] }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        empresa_id: '',
        archivo: null as File | null,
        anio: new Date().getFullYear().toString(),
        tipo_estado: 'balance_general',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('importacion.store'), {
            forceFormData: true, // Necesario para subida de archivos
        });
    }

    return (
        <AppLayout>
            <Head title="Importar Estado Financiero" />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <Card className="max-w-2xl mx-auto">
                    <CardHeader>
                        <CardTitle>Importar Estado Financiero</CardTitle>
                        <CardDescription>Sube un archivo Excel o CSV para importar los detalles de un estado financiero.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {validation_errors.length > 0 && (
                            <Alert variant="destructive">
                                <Terminal className="h-4 w-4" />
                                <AlertTitle>Errores de Validación</AlertTitle>
                                <AlertDescription>
                                    <ul className="list-disc pl-5 mt-2">
                                        {validation_errors.map((error, index) => (
                                            <li key={index}>{error}</li>
                                        ))}
                                    </ul>
                                </AlertDescription>
                            </Alert>
                        )}

                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div>
                                <Label htmlFor="empresa_id">Empresa</Label>
                                <Select onValueChange={(value) => setData('empresa_id', value)} value={data.empresa_id}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona una empresa" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {empresas.map((empresa) => (
                                            <SelectItem key={empresa.id} value={empresa.id.toString()}>
                                                {empresa.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.empresa_id} className="mt-2" />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="anio">Año</Label>
                                    <Input
                                        id="anio"
                                        type="number"
                                        value={data.anio}
                                        onChange={(e) => setData('anio', e.target.value)}
                                    />
                                    <InputError message={errors.anio} className="mt-2" />
                                </div>
                                <div>
                                    <Label htmlFor="tipo_estado">Tipo de Estado</Label>
                                    <Select onValueChange={(value) => setData('tipo_estado', value)} value={data.tipo_estado}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona un tipo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="balance_general">Balance General</SelectItem>
                                            <SelectItem value="estado_resultados">Estado de Resultados</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.tipo_estado} className="mt-2" />
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="archivo">Archivo</Label>
                                <Input
                                    id="archivo"
                                    type="file"
                                    onChange={(e) => setData('archivo', e.target.files ? e.target.files[0] : null)}
                                />
                                <InputError message={errors.archivo} className="mt-2" />
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Importando...' : 'Importar Archivo'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}