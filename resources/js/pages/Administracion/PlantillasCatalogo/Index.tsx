import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

// TODO: Definir los props que se recibirán del controlador
interface Plantilla {
    id: number;
    nombre: string;
    descripcion: string;
}

interface IndexProps {
    plantillas: Plantilla[];
}

export default function PlantillasCatalogoIndex({ plantillas }: IndexProps) {
    return (
        <AppLayout>
            <Head title="Gestión de Plantillas de Catálogo" />
            <div className="container mx-auto py-8">
                <h1 className="text-2xl font-bold mb-6">Gestión de Plantillas de Catálogo</h1>
                
                {/* Aquí irá la tabla para listar las plantillas */}
                <div className="bg-white shadow-md rounded-lg p-6">
                    <p>Tabla de plantillas de catálogo...</p>
                </div>
            </div>
        </AppLayout>
    );
}
