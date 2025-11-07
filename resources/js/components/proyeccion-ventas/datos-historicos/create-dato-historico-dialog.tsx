import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import {
    DatoHistoricoFormSimple,
    DatoHistoricoFormValues,
} from './dato-historico-form-simple';

interface CreateDatoHistoricoDialogProps {
    empresaId: number;
    children: React.ReactNode;
    nextPeriod?: {
        mes: number;
        anio: number;
    } | null;
    hasData: boolean;
    onSuccess?: () => void;
}

export function CreateDatoHistoricoDialog({
    empresaId,
    children,
    nextPeriod,
    hasData,
    onSuccess,
}: CreateDatoHistoricoDialogProps) {
    const [open, setOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    const handleSubmit = (values: DatoHistoricoFormValues) => {
        setIsLoading(true);

        // Si hay datos históricos, usamos el período calculado
        const data =
            hasData && nextPeriod
                ? {
                      anio: nextPeriod.anio,
                      mes: nextPeriod.mes,
                      monto: values.monto,
                  }
                : {
                      anio: values.anio,
                      mes: values.mes,
                      monto: values.monto,
                  };

        router.post(
            `/proyecciones/${empresaId}/datos-historicos`,
            data as Record<string, number>,
            {
                onSuccess: () => {
                    toast.success('Dato histórico añadido correctamente');
                    setOpen(false);
                    setIsLoading(false);
                    // Llamar a la función onSuccess para actualizar el próximo período
                    if (onSuccess) {
                        onSuccess();
                    }
                },
                onError: (errors) => {
                    console.error('Errores:', errors);
                    const errorMessages = Object.values(errors).flat();
                    errorMessages.forEach((error) => {
                        toast.error(error as string);
                    });
                    setIsLoading(false);
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Añadir Dato Histórico</DialogTitle>
                    <DialogDescription>
                        {hasData && nextPeriod
                            ? 'Añade el siguiente período en la cadena de datos históricos.'
                            : 'Esta será tu primera entrada de datos históricos. Puedes elegir el período de inicio.'}
                    </DialogDescription>
                </DialogHeader>
                <DatoHistoricoFormSimple
                    onSubmit={handleSubmit}
                    isLoading={isLoading}
                    isPeriodReadOnly={hasData}
                    nextPeriod={nextPeriod}
                />
            </DialogContent>
        </Dialog>
    );
}
