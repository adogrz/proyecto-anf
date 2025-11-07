import { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

export function FlashMessages() {
    const { flash } = usePage<SharedData>().props;

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
        if (flash?.warning) {
            toast.warning(flash.warning);
        }
        if (flash?.info) {
            toast.info(flash.info);
        }
    }, [flash]);

    // Limpiar los mensajes después de mostrarlos
    useEffect(() => {
        const removeListener = router.on('finish', () => {
            // Los mensajes flash se limpian automáticamente en el siguiente request
        });

        return () => {
            removeListener();
        };
    }, []);

    return null;
}
