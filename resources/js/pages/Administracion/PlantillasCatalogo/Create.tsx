import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function PlantillasCatalogoCreate() {
    return (
        <AppLayout>
            <Head title="Crear Plantilla de Catálogo" />
            <div className="container mx-auto py-8">
                <h1 className="text-2xl font-bold mb-6">Crear Nueva Plantilla de Catálogo</h1>
                
                {/* Aquí irá el formulario de creación */}
                <div className="bg-white shadow-md rounded-lg p-6">
                    <p>Formulario de creación...</p>
                </div>
            </div>
        </AppLayout>
    );
}
