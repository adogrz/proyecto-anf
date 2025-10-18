import React, { useEffect } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/input-error';

// Interfaces
interface Empresa {
    id: number;
    nombre: string;
}

interface CuentaBase {
    id: number;
    nombre: string;
}

interface CatalogoEmpresa {
    id: number;
    codigo_cuenta: string;
    nombre_cuenta: string;
    cuenta_base_id: number | null;
}

interface MapeoProps {
    empresa: Empresa;
    cuentasBase: CuentaBase[];
    catalogoEmpresa: CatalogoEmpresa[];
}

export default function Mapeo({ empresa, cuentasBase, catalogoEmpresa }: MapeoProps) {
    const importForm = useForm({
        archivo: null as File | null,
    });

    const mapeoForm = useForm({
        mapeos: [] as { id: number; cuenta_base_id: string }[],
    });

    useEffect(() => {
        if (catalogoEmpresa) {
            mapeoForm.setData('mapeos', catalogoEmpresa.map(c => ({
                id: c.id,
                cuenta_base_id: c.cuenta_base_id?.toString() || '',
            })));
        }
    }, [catalogoEmpresa]);

    const onFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            importForm.setData('archivo', e.target.files[0]);
        }
    };

    const submitImport = (e: React.FormEvent) => {
        e.preventDefault();
        importForm.post(route('empresas.mapeo.import', empresa.id), {
            preserveScroll: true,
        });
    };

    const handleSelectChange = (index: number, value: string) => {
        const updatedMapeos = [...mapeoForm.data.mapeos];
        updatedMapeos[index].cuenta_base_id = value;
        mapeoForm.setData('mapeos', updatedMapeos);
    };

    const submitMapeo = (e: React.FormEvent) => {
        e.preventDefault();
        mapeoForm.put(route('empresas.mapeo.update', empresa.id), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout>
            <Head title={`Mapeo de Catálogo para ${empresa.nombre}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold">Mapeo de Catálogo: {empresa.nombre}</h1>
                    <Button asChild variant="outline">
                        <Link href={route('empresas.index')}>
                            Volver a Empresas
                        </Link>
                    </Button>
                </div>

                <Card className="mb-8">
                    <CardHeader>
                        <CardTitle>Paso 1: Importar Catálogo de Cuentas</CardTitle>
                        <CardDescription>
                            Sube tu catálogo de cuentas en formato Excel (.xlsx, .xls) o CSV. 
                            El archivo debe contener las columnas 'codigo_cuenta' y 'nombre_cuenta'.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitImport} className="space-y-4">
                            <div>
                                <Label htmlFor="archivo">Archivo del Catálogo</Label>
                                <Input id="archivo" type="file" onChange={onFileChange} className="mt-1" />
                                <InputError message={importForm.errors.archivo} className="mt-2" />
                            </div>
                            <div className="flex justify-end">
                                <Button type="submit" disabled={importForm.processing}>
                                    {importForm.processing ? 'Importando...' : 'Importar Archivo'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {catalogoEmpresa.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Paso 2: Mapear Cuentas</CardTitle>
                            <CardDescription>
                                Asocia cada cuenta de tu catálogo con una cuenta base del sistema.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submitMapeo} className="space-y-4">
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Código</TableHead>
                                                <TableHead>Nombre de tu Cuenta</TableHead>
                                                <TableHead>Cuenta Base del Sistema</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {mapeoForm.data.mapeos.map((mapeo, index) => {
                                                const catalogoItem = catalogoEmpresa.find(c => c.id === mapeo.id);
                                                return (
                                                    <TableRow key={mapeo.id}>
                                                        <TableCell>{catalogoItem?.codigo_cuenta}</TableCell>
                                                        <TableCell>{catalogoItem?.nombre_cuenta}</TableCell>
                                                        <TableCell>
                                                            <Select
                                                                value={mapeo.cuenta_base_id?.toString() || ''}
                                                                onValueChange={(value) => handleSelectChange(index, value)}
                                                            >
                                                                <SelectTrigger>
                                                                    <SelectValue placeholder="Selecciona una cuenta base" />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    {cuentasBase.map((cuenta) => (
                                                                        <SelectItem key={cuenta.id} value={cuenta.id.toString()}>
                                                                            {cuenta.nombre}
                                                                        </SelectItem>
                                                                    ))}
                                                                </SelectContent>
                                                            </Select>
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })}
                                        </TableBody>
                                    </Table>
                                </div>
                                <div className="flex justify-end">
                                    <Button type="submit" disabled={mapeoForm.processing}>
                                        {mapeoForm.processing ? 'Guardando Mapeo...' : 'Guardar Mapeo'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

            </div>
        </AppLayout>
    );
}