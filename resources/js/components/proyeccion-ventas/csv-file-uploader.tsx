import {
    AlertCircleIcon,
    FileSpreadsheetIcon,
    UploadIcon,
    XIcon,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { formatBytes, useFileUpload } from '@/hooks/use-file-upload';

interface CSVFileUploaderProps {
    onFileSelected?: (file: File | null) => void;
    disabled?: boolean;
}

export default function CSVFileUploader({
    onFileSelected,
    disabled = false,
}: CSVFileUploaderProps) {
    const maxSize = 2 * 1024 * 1024; // 2MB (coincide con validación backend)

    const [
        { files, isDragging, errors },
        {
            handleDragEnter,
            handleDragLeave,
            handleDragOver,
            handleDrop,
            openFileDialog,
            removeFile,
            getInputProps,
        },
    ] = useFileUpload({
        maxSize,
        accept: '.csv,text/csv,application/csv',
        multiple: false,
        onFilesChange: (newFiles) => {
            // Notificar al padre cuando cambian los archivos
            onFileSelected?.(
                newFiles.length > 0 && newFiles[0].file instanceof File
                    ? newFiles[0].file
                    : null,
            );
        },
    });

    const file = files[0];

    return (
        <div className="flex flex-col gap-2">
            {/* Drop area */}
            <div
                role="button"
                onClick={openFileDialog}
                onDragEnter={handleDragEnter}
                onDragLeave={handleDragLeave}
                onDragOver={handleDragOver}
                onDrop={handleDrop}
                data-dragging={isDragging || undefined}
                className="flex min-h-40 flex-col items-center justify-center rounded-xl border border-dashed border-input p-4 transition-colors hover:bg-accent/50 has-disabled:pointer-events-none has-disabled:opacity-50 has-[input:focus]:border-ring has-[input:focus]:ring-[3px] has-[input:focus]:ring-ring/50 data-[dragging=true]:bg-accent/50"
            >
                <input
                    {...getInputProps()}
                    className="sr-only"
                    aria-label="Cargar archivo CSV"
                    disabled={disabled || Boolean(file)}
                />

                <div className="flex flex-col items-center justify-center text-center">
                    <div
                        className="mb-2 flex size-11 shrink-0 items-center justify-center rounded-full border bg-background"
                        aria-hidden="true"
                    >
                        <UploadIcon className="size-4 opacity-60" />
                    </div>
                    <p className="mb-1.5 text-sm font-medium">
                        Subir archivo CSV
                    </p>
                    <p className="text-xs text-muted-foreground">
                        Arrastra y suelta o haz clic para seleccionar (máx.{' '}
                        {formatBytes(maxSize)})
                    </p>
                </div>
            </div>

            {errors.length > 0 && (
                <div
                    className="text-destructive flex items-center gap-1 text-xs"
                    role="alert"
                >
                    <AlertCircleIcon className="size-3 shrink-0" />
                    <span>{errors[0]}</span>
                </div>
            )}

            {/* File list */}
            {file && (
                <div className="space-y-2">
                    <div
                        key={file.id}
                        className="flex items-center justify-between gap-2 rounded-xl border px-4 py-2"
                    >
                        <div className="flex items-center gap-3 overflow-hidden">
                            <FileSpreadsheetIcon
                                className="size-4 shrink-0 text-green-600 opacity-80"
                                aria-hidden="true"
                            />
                            <div className="min-w-0">
                                <p className="truncate text-[13px] font-medium">
                                    {file.file.name}
                                </p>
                            </div>
                        </div>

                        <Button
                            size="icon"
                            variant="ghost"
                            className="-me-2 size-8 text-muted-foreground/80 hover:bg-transparent hover:text-foreground"
                            onClick={() => removeFile(files[0]?.id)}
                            aria-label="Eliminar archivo"
                            disabled={disabled}
                        >
                            <XIcon className="size-4" aria-hidden="true" />
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
