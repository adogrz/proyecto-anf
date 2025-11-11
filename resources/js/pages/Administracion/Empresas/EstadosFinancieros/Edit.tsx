import React, { useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Command, CommandInput, CommandEmpty, CommandGroup, CommandItem } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { CaretSortIcon } from '@radix-ui/react-icons'; // Corrected import for CaretSortIcon
import InputError from '@/components/input-error';
import { BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';

// Interfaces
interface Empresa {
    id: number;
    nombre: string;
}

interface CuentaBase {
    id: number;
    nombre: string;
    codigo: string;
    parent_id: number | null;
    children_recursive?: CuentaBase[];
    catalogo_cuentas?: CatalogoCuenta[];
}

interface CatalogoCuenta {
    id: number;
    nombre: string; // Reverted
    codigo: string; // Reverted
    cuenta_base_id: number;
    cuenta_base_nombre: string;
}

interface DetalleEstado {
    id?: number; // Optional for new details
    catalogo_cuenta_id: string;
    valor: string;
}

interface EstadoFinanciero {
    id: number;
    anio: number;
    tipo_estado: string;
    detalles: DetalleEstado[];
}

interface EditProps {
    empresa: Empresa;
    estadoFinanciero: EstadoFinanciero;
    // cuentasBaseRaiz: CuentaBase[]; // Removed as it's no longer passed
    catalogoCuentas: CatalogoCuenta[];
}

const BREADCRUMBS = (empresa: Empresa, estadoFinanciero: EstadoFinanciero): BreadcrumbItem[] => [
    { title: 'Home', href: route('dashboard') },
    { title: 'Empresas', href: route('empresas.index') },
    { title: empresa.nombre, href: route('empresas.show', empresa.id) },
    { title: 'Estados Financieros', href: route('empresas.estados-financieros.index', empresa.id) },
    { title: `${estadoFinanciero.anio} ${estadoFinanciero.tipo_estado.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}`, href: route('empresas.estados-financieros.show', { empresa: empresa.id, estados_financiero: estadoFinanciero.id }) },
    { title: 'Editar', href: route('empresas.estados-financieros.edit', { empresa: empresa.id, estados_financiero: estadoFinanciero.id }) },
];

export default function EstadosFinancierosEdit({ empresa, estadoFinanciero, catalogoCuentas }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        anio: String(estadoFinanciero.anio),
        tipo_estado: estadoFinanciero.tipo_estado,
        detalles: estadoFinanciero.detalles.map(d => ({
            id: d.id,
            catalogo_cuenta_id: String(d.catalogo_cuenta_id),
            valor: String(d.valor),
        })),
        empresa_id: empresa.id, // Hidden field for validation
    });

    const addDetalle = () => {
        setData('detalles', [...data.detalles, { catalogo_cuenta_id: '', valor: '' }]);
    };

    const removeDetalle = (index: number) => {
        const newDetalles = [...data.detalles];
        newDetalles.splice(index, 1);
        setData('detalles', newDetalles);
    };

    const handleDetalleChange = (index: number, field: string, value: string) => {
        const newDetalles = [...data.detalles];
        newDetalles[index] = { ...newDetalles[index], [field]: value };
        setData('detalles', newDetalles);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('empresas.estados-financieros.update', { empresa: empresa.id, estados_financiero: estadoFinanciero.id }));
    };

    return (
        <AppLayout breadcrumbs={BREADCRUMBS(empresa, estadoFinanciero)}>
            <Head title={`Editar Estado Financiero para ${empresa.nombre}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <Card className="max-w-4xl mx-auto">
                    <CardHeader>
                        <CardTitle>Editar Estado Financiero</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="anio">Año</Label>
                                    <Input
                                        id="anio"
                                        type="number"
                                        value={data.anio}
                                        onChange={(e) => setData('anio', e.target.value)}
                                    />
                                    <InputError message={errors.anio} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="tipo_estado">Tipo de Estado</Label>
                                    <Select onValueChange={(value) => setData('tipo_estado', value)} value={data.tipo_estado}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccione el tipo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="balance_general">Balance General</SelectItem>
                                            <SelectItem value="estado_resultados">Estado de Resultados</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.tipo_estado} />
                                </div>
                            </div>

                            <h3 className="text-lg font-semibold mt-6 mb-4">Detalles del Estado Financiero</h3>
                            <div className="space-y-4">
                                {data.detalles.map((detalle, index) => (
                                    <div key={index} className="flex items-end gap-2 w-full"> {/* Added w-full to ensure it takes full width */}
                                        <div className="flex-1 space-y-2 min-w-0"> {/* Added min-w-0 */}
                                            <Label htmlFor={`catalogo_cuenta_id-${index}`}>Cuenta</Label>
                                            <Popover>
                                                <PopoverTrigger asChild>
                                                    <Button
                                                        variant="outline"
                                                        role="combobox"
                                                        className="w-full justify-between overflow-hidden text-ellipsis whitespace-nowrap" // Added overflow-hidden, text-ellipsis, whitespace-nowrap
                                                    >
                                                        {detalle.catalogo_cuenta_id
                                                            ? catalogoCuentas.find(
                                                                  (cuenta) => String(cuenta.id) === detalle.catalogo_cuenta_id
                                                              )?.codigo + ' - ' + catalogoCuentas.find(
                                                                  (cuenta) => String(cuenta.id) === detalle.catalogo_cuenta_id
                                                              )?.nombre
                                                              : "Seleccione una cuenta..."}
                                                        <CaretSortIcon className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                    </Button>
                                                </PopoverTrigger>
                                                <PopoverContent className="w-[--radix-popover-trigger-width] p-0 max-h-[200px] overflow-y-auto">
                                                    <Command>
                                                        <CommandInput placeholder="Buscar cuenta..." className="h-9" />
                                                        <CommandEmpty>No se encontraron cuentas.</CommandEmpty>
                                                        <CommandGroup>
                                                            {catalogoCuentas.map((cuenta) => (
                                                                <CommandItem
                                                                    value={`${cuenta.codigo} - ${cuenta.nombre}`} // Reverted to codigo and nombre
                                                                    key={cuenta.id}
                                                                    onSelect={() => {
                                                                        handleDetalleChange(index, 'catalogo_cuenta_id', String(cuenta.id));
                                                                    }}
                                                                >
                                                                    {cuenta.codigo} - {cuenta.nombre}
                                                                </CommandItem>
                                                            ))}
                                                        </CommandGroup>
                                                    </Command>
                                                </PopoverContent>
                                            </Popover>
                                            <InputError message={errors[`detalles.${index}.catalogo_cuenta_id`]} />
                                        </div>
                                        <div className="flex-1 space-y-2 min-w-0"> {/* Added min-w-0 */}
                                            <Label htmlFor={`valor-${index}`}>Valor</Label>
                                            <Input
                                                id={`valor-${index}`}
                                                type="number"
                                                step="0.01"
                                                value={detalle.valor}
                                                onChange={(e) => handleDetalleChange(index, 'valor', e.target.value)}
                                            />
                                            <InputError message={errors[`detalles.${index}.valor`]} />
                                        </div>
                                        <Button type="button" variant="destructive" className="flex-shrink-0" onClick={() => removeDetalle(index)}>
                                            Eliminar
                                        </Button>
                                    </div>
                                ))}
                            </div>
                            <Button type="button" variant="outline" onClick={addDetalle}>
                                Añadir Detalle
                            </Button>

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
