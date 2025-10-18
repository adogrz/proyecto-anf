import React from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/input-error';
import { Ratio } from '@/pages/Administracion/Sectores/ratios-columns';

interface EditProps {
    ratio: Ratio;
}

export default function RatiosEdit({ ratio }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        nombre_ratio: ratio.nombre_ratio || '',
        valor: ratio.valor.toString() || '',
        tipo_ratio: ratio.tipo_ratio || 'estandar_sector',
        mensaje_superior: ratio.mensaje_superior || '',
        mensaje_inferior: ratio.mensaje_inferior || '',
        mensaje_igual: ratio.mensaje_igual || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('ratios.update', ratio.id));
    };

    return (
        <AppLayout>
            <Head title={`Editar Ratio: ${ratio.nombre_ratio}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <Card className="max-w-2xl mx-auto">
                    <CardHeader>
                        <CardTitle>Editar Ratio</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="nombre_ratio">Nombre del Ratio</Label>
                                <Input id="nombre_ratio" value={data.nombre_ratio} onChange={(e) => setData('nombre_ratio', e.target.value)} autoFocus />
                                <InputError message={errors.nombre_ratio} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="valor">Valor</Label>
                                    <Input id="valor" type="number" step="0.0001" value={data.valor} onChange={(e) => setData('valor', e.target.value)} />
                                    <InputError message={errors.valor} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="tipo_ratio">Tipo</Label>
                                    <Input id="tipo_ratio" value={data.tipo_ratio} onChange={(e) => setData('tipo_ratio', e.target.value)} />
                                    <InputError message={errors.tipo_ratio} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="mensaje_superior">Mensaje (si es superior)</Label>
                                <Textarea id="mensaje_superior" value={data.mensaje_superior} onChange={(e) => setData('mensaje_superior', e.target.value)} />
                                <InputError message={errors.mensaje_superior} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="mensaje_inferior">Mensaje (si es inferior)</Label>
                                <Textarea id="mensaje_inferior" value={data.mensaje_inferior} onChange={(e) => setData('mensaje_inferior', e.target.value)} />
                                <InputError message={errors.mensaje_inferior} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="mensaje_igual">Mensaje (si es igual)</Label>
                                <Textarea id="mensaje_igual" value={data.mensaje_igual} onChange={(e) => setData('mensaje_igual', e.target.value)} />
                                <InputError message={errors.mensaje_igual} />
                            </div>

                            <div className="flex justify-end space-x-4">
                                <Button asChild variant="outline">
                                    <Link href={route('sectores.show', ratio.sector_id)}>Cancelar</Link>
                                </Button>
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