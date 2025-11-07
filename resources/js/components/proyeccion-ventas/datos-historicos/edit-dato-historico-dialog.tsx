import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { DatoVentaHistorico } from '@/types/proyeccion-ventas';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import {
    DatoHistoricoForm,
    DatoHistoricoFormValues,
} from './dato-historico-form';

interface EditDatoHistoricoDialogProps {
    dato: DatoVentaHistorico;
    empresaId: number;
    children: React.ReactNode;
    onSuccess?: () => void;
}

const MESES = [
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre',
];

export function EditDatoHistoricoDialog({
    dato,
    empresaId,
    children,
    onSuccess,
}: EditDatoHistoricoDialogProps) {
    const [open, setOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    const handleSubmit = (values: DatoHistoricoFormValues) => {
        setIsLoading(true);

        // Solo enviamos el monto, el período no se puede editar
        const data = {
            monto: values.monto,
        };

        router.put(
            `/proyecciones/${empresaId}/datos-historicos/${dato.id}`,
            data as Record<string, number>,
            {
                onSuccess: () => {
                    toast.success('Dato histórico actualizado correctamente');
                    setOpen(false);
                    setIsLoading(false);
                    // Llamar a la función onSuccess si existe
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

    const getMesNombre = (mes: number) => {
        return MESES[mes - 1] || mes.toString();
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Editar Dato Histórico</DialogTitle>
                    <DialogDescription>
                        Editando el dato de{' '}
                        <strong>
                            {getMesNombre(dato.mes)} {dato.anio}
                        </strong>
                        . Solo puedes modificar el monto.
                    </DialogDescription>
                </DialogHeader>
                <DatoHistoricoForm
                    defaultValues={{
                        anio: dato.anio,
                        mes: dato.mes,
                        monto: dato.monto,
                    }}
                    onSubmit={handleSubmit}
                    isLoading={isLoading}
                    isPeriodReadOnly={true}
                    nextPeriod={{
                        mes: dato.mes,
                        anio: dato.anio,
                    }}
                />
            </DialogContent>
        </Dialog>
    );
}
