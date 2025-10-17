import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

// TODO: Definir los props que se recibirán del controlador
interface Plantilla {
    id: number;
    nombre: string;
    descripcion: string;
}

interface EditProps {
    plantilla: Plantilla;
}

export default function PlantillasCatalogoEdit({ plantilla }: EditProps) {
    return (
        <AppLayout>
            <Head title={`Editar Plantilla: ${plantilla.nombre}`} />
            <div className="container mx-auto py-8">
                <h1 className="text-2xl font-bold mb-6">{`Editar Plantilla: ${plantilla.nombre}`}</h1>
                
                {/* Aquí irá el formulario de edición */}
                <div className="bg-white shadow-md rounded-lg p-6">
                    <p>Formulario de edición...</p>
                </div>
            </div>
        </AppLayout>
    );
}
