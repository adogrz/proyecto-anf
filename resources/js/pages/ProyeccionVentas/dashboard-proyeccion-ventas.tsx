import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function ProyeccionesShow() {
    return (
        <AppLayout>
            <Head title="Dashboard de Proyección de Ventas" />
            <div className="flex h-full flex-1 flex-col gap-2 overflow-x-auto rounded-xl p-4">
                <h1 className="text-xl font-semibold">
                    Dashboard de Proyección de Ventas
                </h1>
            </div>
        </AppLayout>
    );
}
