import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import InputError from '@/components/input-error';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { CuentaBase } from './columns';
import { FormEventHandler } from 'react';

interface PlantillaCatalogo {
    id: number;
    nombre: string;
}

interface CreateProps {
    plantillas: PlantillaCatalogo[];
    cuentasBase: CuentaBase[];
}

export default function Create({ plantillas, cuentasBase }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        plantilla_catalogo_id: '',
        codigo: '',
        nombre: '',
        tipo_cuenta: '',
        naturaleza: '',
        parent_id: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('cuentas-base.store'));
    };

    return (
        <AppLayout>
            <Head title="Crear Cuenta Base" />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Crear Nueva Cuenta Base</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <Label htmlFor="plantilla_catalogo_id">Plantilla de Catálogo</Label>
                                <Select
                                    onValueChange={(value) => setData('plantilla_catalogo_id', value)}
                                    value={data.plantilla_catalogo_id}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona una plantilla" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {plantillas.map(plantilla => (
                                            <SelectItem key={plantilla.id} value={plantilla.id.toString()}>
                                                {plantilla.nombre}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.plantilla_catalogo_id} className="mt-2" />
                            </div>

                            <div>
                                <Label htmlFor="codigo">Código</Label>
                                <Input
                                    id="codigo"
                                    type="text"
                                    value={data.codigo}
                                    onChange={(e) => setData('codigo', e.target.value)}
                                    required
                                />
                                <InputError message={errors.codigo} className="mt-2" />
                            </div>

                            <div>
                                <Label htmlFor="nombre">Nombre</Label>
                                <Input
                                    id="nombre"
                                    type="text"
                                    value={data.nombre}
                                    onChange={(e) => setData('nombre', e.target.value)}
                                    required
                                />
                                <InputError message={errors.nombre} className="mt-2" />
                            </div>

                            <div>
                                <Label htmlFor="tipo_cuenta">Tipo de Cuenta</Label>
                                <Select
                                    onValueChange={(value) => setData('tipo_cuenta', value)}
                                    value={data.tipo_cuenta}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona un tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="AGRUPACION">AGRUPACION</SelectItem>
                                        <SelectItem value="DETALLE">DETALLE</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.tipo_cuenta} className="mt-2" />
                            </div>

                            <div>
                                <Label htmlFor="naturaleza">Naturaleza</Label>
                                <Select
                                    onValueChange={(value) => setData('naturaleza', value)}
                                    value={data.naturaleza}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona una naturaleza" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="DEUDORA">DEUDORA</SelectItem>
                                        <SelectItem value="ACREEDORA">ACREEDORA</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.naturaleza} className="mt-2" />
                            </div>

                            <div>
                                <Label htmlFor="parent_id">Cuenta Padre (Opcional)</Label>
                                <Select
                                    onValueChange={(value) => setData('parent_id', value === '' ? null : value)}
                                    value={data.parent_id?.toString() || ''}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona una cuenta padre" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={null}>Ninguna (Cuenta Principal)</SelectItem>
                                        {cuentasBase
                                            .filter(cuenta => cuenta.plantilla_catalogo_id === parseInt(data.plantilla_catalogo_id))
                                            .map(cuenta => (
                                                <SelectItem key={cuenta.id} value={cuenta.id.toString()}>
                                                    {cuenta.nombre} ({cuenta.codigo})
                                                </SelectItem>
                                            ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.parent_id} className="mt-2" />
                            </div>

                            <Button type="submit" disabled={processing}>
                                Crear Cuenta Base
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}