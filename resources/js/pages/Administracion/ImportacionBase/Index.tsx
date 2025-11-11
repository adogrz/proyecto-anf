import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { PageProps, PlantillaCatalogo, BreadcrumbItem } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useState } from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Progress } from '@/components/ui/progress';
import InputError from '@/components/input-error';
import Heading from '@/components/heading';

type PreviewData = Array<string[]>;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Administración',
    },
    {
        title: 'Importar Catálogo Base',
        href: route('admin.importacion-base.index'),
    },
];

export default function ImportacionBaseIndex({ plantillas }: PageProps<{ plantillas: PlantillaCatalogo[] }>) {
    const [previewData, setPreviewData] = useState<PreviewData>([]);
    const [isLoadingPreview, setIsLoadingPreview] = useState(false);
    const [fileName, setFileName] = useState('');

    const { data, setData, post, processing, errors, progress } = useForm({
        file: null as File | null,
        plantilla_catalogo_id: '',
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            setData('file', e.target.files[0]);
            setFileName(e.target.files[0].name);
            setPreviewData([]);
        }
    };

    const handlePreview = async () => {
        if (!data.file) return;

        setIsLoadingPreview(true);
        setPreviewData([]);

        const formData = new FormData();
        formData.append('file', data.file);

        try {
            const response = await fetch(route('admin.importacion-base.preview'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content,
                },
            });

            if (!response.ok) {
                throw new Error('Error al previsualizar el archivo.');
            }

            const json: PreviewData = await response.json();
            setPreviewData(json);
        } catch (error) {
            console.error(error);
        } finally {
            setIsLoadingPreview(false);
        }
    };

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(route('admin.importacion-base.import'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Importar Catálogo Base" />
            <div className="space-y-4 p-4 sm:p-6 lg:p-8">
                <Heading title="Importar Catálogo Base" description="Sube un archivo (CSV o Excel) para poblar una plantilla de catálogo." />

                <Card>
                    <CardHeader>
                        <CardTitle>Paso 1: Seleccionar Plantilla y Archivo</CardTitle>
                        <CardDescription>
                            Elige la plantilla de catálogo que deseas poblar y el archivo que contiene las cuentas.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="plantilla">Plantilla de Catálogo</Label>
                                    <Select
                                        onValueChange={(value) => setData('plantilla_catalogo_id', value)}
                                        value={data.plantilla_catalogo_id}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecciona una plantilla..." />
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
                                <div className="space-y-2">
                                    <Label htmlFor="file">Archivo</Label>
                                    <div className="flex gap-2">
                                        <Input id="file" type="file" onChange={handleFileChange} className="flex-grow" />
                                        <Button type="button" onClick={handlePreview} disabled={!data.file || isLoadingPreview}>
                                            {isLoadingPreview ? 'Cargando...' : 'Previsualizar'}
                                        </Button>
                                    </div>
                                    {fileName && <p className="text-sm text-muted-foreground">Archivo seleccionado: {fileName}</p>}
                                    <InputError message={errors.file} />
                                </div>
                            </div>

                            {progress && (
                                <Progress value={progress.percentage} className="w-full" />
                            )}

                            <Button type="submit" disabled={processing || !data.file || !data.plantilla_catalogo_id}>
                                {processing ? 'Importando...' : 'Importar Catálogo'}
                            </Button>
                            <InputError message={errors.general} />
                        </form>
                    </CardContent>
                </Card>

                {previewData.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Paso 2: Previsualización</CardTitle>
                            <CardDescription>
                                Se muestran las primeras 100 filas de tu archivo. Verifica que los datos sean correctos.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-auto border rounded-md max-h-96">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Código</TableHead>
                                            <TableHead>Nombre de Cuenta</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {previewData.map((row, index) => (
                                            <TableRow key={index}>
                                                <TableCell>{row[0]}</TableCell>
                                                <TableCell>{row[1]}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
