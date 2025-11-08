import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Progress } from '@/components/ui/progress';
import { router } from '@inertiajs/react';
import { AlertCircle, Upload } from 'lucide-react';
import { ReactNode, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import CSVFileUploader from './csv-file-uploader';

interface ImportCSVDialogProps {
    empresaId: number;
    children: ReactNode;
}

interface ErroresFila {
    fila: number;
    errores: string[];
}

export function ImportCSVDialog({ empresaId, children }: ImportCSVDialogProps) {
    const [open, setOpen] = useState(false);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [isUploading, setIsUploading] = useState(false);
    const [uploadProgress, setUploadProgress] = useState(0);
    const [uploadError, setUploadError] = useState<string | null>(null);
    const [erroresFila, setErroresFila] = useState<ErroresFila[]>([]);
    const intervalRef = useRef<number | null>(null);
    const successToastRef = useRef(false); // evitar doble toast en StrictMode

    useEffect(() => {
        // Limpieza si el componente se desmonta por navegación Inertia
        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
                intervalRef.current = null;
            }
        };
    }, []);

    const handleFileSelected = (file: File | null) => {
        setSelectedFile(file);
        // Limpiar errores previos cuando se selecciona un nuevo archivo
        setUploadError(null);
        setErroresFila([]);
    };

    const startProgressSimulation = () => {
        setUploadProgress(15);
        if (intervalRef.current) {
            clearInterval(intervalRef.current);
        }
        intervalRef.current = window.setInterval(() => {
            setUploadProgress((prev) => {
                if (prev >= 85) {
                    if (intervalRef.current) {
                        clearInterval(intervalRef.current);
                        intervalRef.current = null;
                    }
                    return 85;
                }
                return prev + 10;
            });
        }, 250);
    };

    const clearProgress = (finalValue?: number) => {
        if (intervalRef.current) {
            clearInterval(intervalRef.current);
            intervalRef.current = null;
        }
        if (finalValue !== undefined) {
            setUploadProgress(finalValue);
        }
        setIsUploading(false);
    };

    const handleImport = () => {
        if (!selectedFile) return;

        setIsUploading(true);
        setUploadProgress(10);
        setUploadError(null);
        setErroresFila([]);

        const formData = new FormData();
        formData.append('csv_file', selectedFile);

        startProgressSimulation();

        router.post(`/proyecciones/${empresaId}/importar-csv`, formData, {
            forceFormData: true,
            onSuccess: (page) => {
                clearProgress(100);
                // Leer flash success si viene del backend
                const p = page as unknown as {
                    props?: { flash?: { success?: string } };
                };
                const flashSuccess = p?.props?.flash?.success;
                if (!successToastRef.current) {
                    toast.success(flashSuccess || 'Importación exitosa.');
                    successToastRef.current = true;
                }
                // Cerrar diálogo después de pequeño delay para que el usuario vea el 100%
                setTimeout(() => {
                    setOpen(false);
                    setSelectedFile(null);
                    setUploadProgress(0);
                }, 400);
            },
            onError: (errors) => {
                clearProgress(0);
                successToastRef.current = false; // permitir nuevo intento

                // Manejar errores
                if (errors.csv_file) {
                    setUploadError(errors.csv_file as string);
                }

                if (errors.errores_fila) {
                    // El backend devuelve un array de objetos con { fila, errores }
                    const erroresFilaData = errors.errores_fila as unknown;
                    if (Array.isArray(erroresFilaData)) {
                        setErroresFila(erroresFilaData as ErroresFila[]);
                    }
                }
            },
            onFinish: () => {
                // Solo limpiar intervalo; no cerrar aquí para distinguir éxito vs error
                if (intervalRef.current) {
                    clearInterval(intervalRef.current);
                    intervalRef.current = null;
                }
            },
        });
    };

    const handleClose = () => {
        if (!isUploading) {
            setOpen(false);
            // Limpiar estados
            setSelectedFile(null);
            setUploadProgress(0);
            setUploadError(null);
            setErroresFila([]);
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        Importar Datos Históricos desde CSV
                    </DialogTitle>
                    <DialogDescription>
                        Sube un archivo CSV con tus datos de ventas históricas.
                        El archivo debe tener las columnas: Anio, Mes,
                        Monto_Venta.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-4">
                    {/* File Uploader */}
                    <CSVFileUploader
                        onFileSelected={handleFileSelected}
                        disabled={isUploading}
                    />

                    {/* Progress Bar */}
                    {isUploading && (
                        <div className="space-y-2">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">
                                    Procesando archivo...
                                </span>
                                <span className="font-medium">
                                    {uploadProgress}%
                                </span>
                            </div>
                            <Progress value={uploadProgress} />
                        </div>
                    )}

                    {/* Error Message */}
                    {uploadError && (
                        <div className="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
                            <AlertCircle className="size-5 shrink-0 text-red-600 dark:text-red-400" />
                            <div className="flex-1">
                                <p className="text-sm font-medium text-red-900 dark:text-red-100">
                                    Error en la importación
                                </p>
                                <p className="mt-1 text-sm text-red-700 dark:text-red-200">
                                    {uploadError}
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Errores por Fila */}
                    {erroresFila.length > 0 && (
                        <div className="space-y-2">
                            <p className="text-sm font-medium text-red-900 dark:text-red-100">
                                Se encontraron {erroresFila.length} error(es) en
                                el archivo:
                            </p>
                            <div className="max-h-60 overflow-y-auto rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-900 dark:bg-red-950">
                                <ul className="space-y-2 text-sm">
                                    {erroresFila.map((error, index) => (
                                        <li
                                            key={index}
                                            className="text-red-800 dark:text-red-200"
                                        >
                                            <span className="font-semibold">
                                                Fila {error.fila}:
                                            </span>{' '}
                                            {error.errores.join(', ')}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    )}

                    {/* Información adicional */}
                    {!uploadError && !isUploading && (
                        <div className="rounded-lg border bg-muted/50 p-3">
                            <p className="text-xs text-muted-foreground">
                                <strong>Nota:</strong> El archivo será validado
                                antes de importarse. Los datos deben ser
                                continuos cronológicamente y no pueden tener
                                vacíos entre períodos.
                            </p>
                        </div>
                    )}
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={handleClose}
                        disabled={isUploading}
                    >
                        Cancelar
                    </Button>
                    <Button
                        onClick={handleImport}
                        disabled={!selectedFile || isUploading}
                    >
                        {isUploading ? (
                            <>
                                <div className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-background border-t-transparent" />
                                Importando...
                            </>
                        ) : (
                            <>
                                <Upload className="mr-2 h-4 w-4" />
                                Importar
                            </>
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
