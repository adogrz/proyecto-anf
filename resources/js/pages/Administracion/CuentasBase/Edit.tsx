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
import { type BreadcrumbItem, type Empresa, type PlantillaCatalogo } from '@/types';

interface EditProps {
    allCuentasBase: CuentaBase[];
    cuentaBase: CuentaBase;
    plantilla: PlantillaCatalogo;
    breadcrumbs?: BreadcrumbItem[];
    empresa: Empresa;
}

export default function Edit({ allCuentasBase, cuentaBase, plantilla, breadcrumbs, empresa }: EditProps) {
    if (!cuentaBase) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Error" />
                <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>Error al cargar la cuenta base</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p>No se pudo cargar la información de la cuenta base. Por favor, intente de nuevo.</p>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    const { data, setData, put, processing, errors } = useForm({
        codigo: cuentaBase.codigo,
        nombre: cuentaBase.nombre,
        tipo_cuenta: cuentaBase.tipo_cuenta,
        naturaleza: cuentaBase.naturaleza,
        parent_id: cuentaBase.parent_id ? String(cuentaBase.parent_id) : '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        console.log({ empresa, cuentaBase });
        put(route('empresas.cuentas-base.update', { empresa: empresa.id, cuentas_base: cuentaBase.id }));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar Cuenta Base: ${cuentaBase.nombre}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Editar Cuenta Base en {plantilla.nombre}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
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
                                    onValueChange={(value) => setData('parent_id', value === '_null' ? '' : value)}
                                    value={data.parent_id || '_null'}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona una cuenta padre" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_null">Ninguna (Cuenta Principal)</SelectItem>
                                        {allCuentasBase
                                            .filter(cuenta => cuenta.id !== cuentaBase.id)
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
                                Actualizar Cuenta Base
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}