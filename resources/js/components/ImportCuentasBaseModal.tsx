
import React, { FormEventHandler, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { Upload, Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Progress } from '@/components/ui/progress';

interface PlantillaCatalogo {
    id: number;
    nombre: string;
}

interface ImportCuentasBaseModalProps {
    plantilla: PlantillaCatalogo;
    isOpen: boolean;
    onClose: () => void;
}

interface PreviewResponse {
    headers: string[];
    preview: Record<string, string>[];
    total_rows: number;
    parsing_errors: string[];
}

export default function ImportCuentasBaseModal({ plantilla, isOpen, onClose }: ImportCuentasBaseModalProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        plantilla_catalogo_id: plantilla.id.toString(),
        file: null as File | null,
    });

    const [isDragOver, setIsDragOver] = useState(false);
    const [previewData, setPreviewData] = useState<PreviewResponse | null>(null);
    const [isPreviewLoading, setIsPreviewLoading] = useState(false);
    const [importProgress, setImportProgress] = useState(0);

    const handleFileChange = (file: File | null) => {
        setData('file', file);
        setPreviewData(null); // Clear previous preview

        if (file) {
            setIsPreviewLoading(true);
            const formData = new FormData();
            formData.append('plantilla_catalogo_id', plantilla.id.toString());
            formData.append('file', file);

            // Use a direct fetch or Inertia.post with forceFormData for preview
            // Since Inertia.post doesn't directly return JSON, we'll use fetch for preview
            fetch(route('admin.importacion-cuentas-base.preview'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content,
                },
            })
            .then(response => response.json())
            .then((response: PreviewResponse) => {
                if (response.parsing_errors && response.parsing_errors.length > 0) {
                    response.parsing_errors.forEach(error => toast.error(error));
                }
                setPreviewData(response);
            })
            .catch(error => {
                console.error('Preview error:', error);
                toast.error('Error al generar la previsualización del archivo.');
            })
            .finally(() => {
                setIsPreviewLoading(false);
            });
        }
    };

    const handleDragOver = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragOver(true);
    };

    const handleDragLeave = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragOver(false);
    };

    const handleDrop = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragOver(false);
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            handleFileChange(e.dataTransfer.files[0]);
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!data.file) {
            toast.error('Por favor, selecciona un archivo para importar.');
            return;
        }

        post(route('admin.importacion-cuentas-base.store'), {
            onSuccess: () => {
                toast.success('Cuentas base importadas con éxito.');
                reset();
                onClose();
            },
            onError: (err) => {
                console.error('Import error:', err);
                toast.error('Error al importar cuentas base. Revisa los errores.');
            },
            onProgress: (event) => {
                if (event.total) {
                    setImportProgress(Math.round((event.loaded / event.total) * 100));
                }
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[800px]">
                <DialogHeader>
                    <DialogTitle>Importar Cuentas Base</DialogTitle>
                    <DialogDescription>
                        Sube un archivo CSV o Excel para importar masivamente las cuentas base a la plantilla "{plantilla.nombre}".
                        El archivo debe tener las columnas: <strong>codigo, nombre, tipo_cuenta, naturaleza, parent_codigo</strong>.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-6 py-4">
                    <div>
                        <Label htmlFor="plantilla_catalogo_id">Plantilla de Catálogo</Label>
                        <Input
                            id="plantilla_catalogo_id"
                            value={data.plantilla_catalogo_id}
                            disabled
                            className="bg-gray-100"
                        />
                    </div>

                    <div
                        className={`border-2 border-dashed rounded-md p-6 text-center cursor-pointer transition-colors ${
                            isDragOver ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'
                        }`}
                        onDragOver={handleDragOver}
                        onDragLeave={handleDragLeave}
                        onDrop={handleDrop}
                        onClick={() => document.getElementById('file-upload-input')?.click()}
                    >
                        <Input
                            id="file-upload-input"
                            type="file"
                            accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                            className="hidden"
                            onChange={(e) => handleFileChange(e.target.files ? e.target.files[0] : null)}
                        />
                        {data.file ? (
                            <p className="text-gray-700">Archivo seleccionado: <span className="font-medium">{data.file.name}</span></p>
                        ) : (
                            <p className="text-gray-500">Arrastra y suelta tu archivo aquí, o haz clic para seleccionar.</p>
                        )}
                        <InputError message={errors.file} className="mt-2" />
                    </div>

                    {isPreviewLoading && (
                        <div className="flex items-center justify-center space-x-2">
                            <Loader2 className="h-5 w-5 animate-spin" />
                            <p>Generando previsualización...</p>
                        </div>
                    )}

                    {previewData && previewData.parsing_errors && previewData.parsing_errors.length > 0 && (
                        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <strong className="font-bold">Errores de formato en el archivo:</strong>
                            <ul className="mt-2 list-disc list-inside">
                                {previewData.parsing_errors.map((error, index) => (
                                    <li key={index}>{error}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {previewData && previewData.preview && previewData.preview.length > 0 && (
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold">Previsualización de las primeras {previewData.preview.length} filas:</h3>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        {previewData.headers.map((header, index) => (
                                            <TableHead key={index}>{header}</TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {previewData.preview.map((row, rowIndex) => (
                                        <TableRow key={rowIndex}>
                                            {previewData.headers.map((header, colIndex) => (
                                                <TableCell key={colIndex}>{row[header]}</TableCell>
                                            ))}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            <p className="text-sm text-gray-600">Total de filas a importar: {previewData.total_rows}</p>
                        </div>
                    )}

                    {Object.keys(errors).length > 0 && (
                        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <strong className="font-bold">Errores de importación:</strong>
                            <ul className="mt-2 list-disc list-inside">
                                {Object.values(errors).map((error, index) => (
                                    <li key={index}>{error}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {processing && importProgress > 0 && (
                        <div className="space-y-2">
                            <Label>Progreso de Importación</Label>
                            <Progress value={importProgress} className="w-full" />
                            <p className="text-sm text-gray-600 text-center">{importProgress}%</p>
                        </div>
                    )}

                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>Cancelar</Button>
                        <Button type="submit" disabled={processing || isPreviewLoading || !data.file || (previewData && previewData.parsing_errors.length > 0)}>
                            {processing ? 'Importando...' : 'Confirmar Importación'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
