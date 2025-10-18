
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function HistorialCuenta() {
    return (
        <AppLayout>
            <Head title="Historial de Cuenta" />
            <div className="container mx-auto py-8">
                <h1 className="text-2xl font-bold">Historial de Cuenta (Pendiente)</h1>
            </div>
        </AppLayout>
    );
}
