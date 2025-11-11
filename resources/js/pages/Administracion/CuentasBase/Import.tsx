
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import InputError from '@/components/input-error';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { FormEventHandler } from 'react';
import { type BreadcrumbItem } from '@/types';

interface PlantillaCatalogo {
    id: number;
    nombre: string;
}

interface ImportProps {
    plantillas: PlantillaCatalogo[];
    breadcrumbs?: BreadcrumbItem[];
}

export default function Import({ plantillas, breadcrumbs }: ImportProps) {
    const { data, setData, post, processing, errors } = useForm({
        plantilla_catalogo_id: '',
        file: null as File | null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.importacion-cuentas-base.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Importar Cuentas Base" />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <Card className="max-w-2xl mx-auto">
                    <CardHeader>
                        <CardTitle>Importar Cuentas Base</CardTitle>
                        <CardDescription>
                            Sube un archivo CSV para importar masivamente las cuentas base a una plantilla de catálogo.
                            El archivo debe tener las columnas: <strong>codigo, nombre, tipo_cuenta, naturaleza, parent_codigo</strong>.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {Object.keys(errors).length > 0 && (
                            <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <strong className="font-bold">Errores de importación:</strong>
                                <ul className="mt-2 list-disc list-inside">
                                    {Object.values(errors).map((error, index) => (
                                        <li key={index}>{error}</li>
                                    ))}
                                </ul>
                            </div>
                        )}
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <Label htmlFor="plantilla_catalogo_id">Plantilla de Catálogo de Destino</Label>
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
                                <Label htmlFor="file">Archivo CSV</Label>
                                <Input
                                    id="file"
                                    type="file"
                                    accept=".csv"
                                    onChange={(e) => setData('file', e.target.files ? e.target.files[0] : null)}
                                />
                                <InputError message={errors.file} className="mt-2" />
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Importando...' : 'Importar Cuentas'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
