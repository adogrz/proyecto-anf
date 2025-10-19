
import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { Empresa, CuentaBase } from '@/types';

interface CreateProps {
    empresa: Empresa;
    cuentasBase: CuentaBase[];
}

export default function CatalogosCuentasCreate({ empresa, cuentasBase }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        codigo_cuenta: '',
        nombre_cuenta: '',
        cuenta_base_id: '' as string | number,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('empresas.catalogos.store', empresa.id));
    };

    return (
        <AppLayout>
            <Head title={`Añadir Cuenta a ${empresa.nombre}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <Card className="max-w-2xl mx-auto">
                    <CardHeader>
                        <CardTitle>Añadir Nueva Cuenta al Catálogo de {empresa.nombre}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="codigo_cuenta">Código de Cuenta</Label>
                                <Input id="codigo_cuenta" value={data.codigo_cuenta} onChange={(e) => setData('codigo_cuenta', e.target.value)} autoFocus />
                                <InputError message={errors.codigo_cuenta} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="nombre_cuenta">Nombre de la Cuenta</Label>
                                <Input id="nombre_cuenta" value={data.nombre_cuenta} onChange={(e) => setData('nombre_cuenta', e.target.value)} />
                                <InputError message={errors.nombre_cuenta} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="cuenta_base_id">Mapear a (Cuenta Base)</Label>
                                <select id="cuenta_base_id" value={data.cuenta_base_id} onChange={(e) => setData('cuenta_base_id', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">-- No Mapear --</option>
                                    {cuentasBase.map((cuenta) => (
                                        <option key={cuenta.id} value={cuenta.id}>{cuenta.nombre}</option>
                                    ))}
                                </select>
                                <InputError message={errors.cuenta_base_id} />
                            </div>

                            <div className="flex items-center justify-end gap-4">
                                <Button asChild variant="outline">
                                    <Link href={route('empresas.catalogos.index', empresa.id)}>Cancelar</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Guardando...' : 'Crear Cuenta'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
