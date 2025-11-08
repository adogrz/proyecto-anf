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
import { DatoVentaHistorico } from '@/types/proyeccion-ventas';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';

interface DeleteDatoHistoricoDialogProps {
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

export function DeleteDatoHistoricoDialog({
    dato,
    empresaId,
    children,
    onSuccess,
}: DeleteDatoHistoricoDialogProps) {
    const [open, setOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    const handleDelete = () => {
        setIsLoading(true);
        router.delete(
            `/proyecciones/${empresaId}/datos-historicos/${dato.id}` as string,
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Dato histórico eliminado correctamente');
                    setOpen(false);
                    setIsLoading(false);
                    onSuccess?.();
                },
                onError: (errors) => {
                    const errorMessages = Object.values(errors).flat();
                    if (errorMessages.length === 0) {
                        toast.error('No se pudo eliminar el dato.');
                    } else {
                        errorMessages.forEach((e) => toast.error(String(e)));
                    }
                    setIsLoading(false);
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="sm:max-w-[480px]">
                <DialogHeader>
                    <DialogTitle>Eliminar Dato de Venta Histórico</DialogTitle>
                    <DialogDescription>
                        Vas a eliminar el dato de{' '}
                        <strong>{MESES[dato.mes - 1] || dato.mes}</strong>{' '}
                        <strong>{dato.anio}</strong>.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => setOpen(false)}
                        disabled={isLoading}
                    >
                        Cancelar
                    </Button>
                    <Button onClick={handleDelete} disabled={isLoading}>
                        Confirmar eliminación
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
