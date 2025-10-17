import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

// TODO: Definir la estructura jerárquica de las cuentas base
interface CuentaBaseNode {
    id: number;
    codigo: string;
    nombre: string;
    children: CuentaBaseNode[];
}

interface Plantilla {
    id: number;
    nombre: string;
    descripcion: string;
    cuentas_base_tree: CuentaBaseNode[]; // Árbol de cuentas
}

interface ShowProps {
    plantilla: Plantilla;
}

export default function PlantillasCatalogoShow({ plantilla }: ShowProps) {
    return (
        <AppLayout>
            <Head title={`Ver Plantilla: ${plantilla.nombre}`} />
            <div className="container mx-auto py-8">
                <h1 className="text-2xl font-bold mb-6">{`Detalle de Plantilla: ${plantilla.nombre}`}</h1>
                
                {/* Aquí se mostrará el árbol de cuentas base */}
                <div className="bg-white shadow-md rounded-lg p-6">
                    <h2 className="text-xl font-semibold mb-4">Estructura de Cuentas</h2>
                    <p>Visualización del árbol de cuentas...</p>
                </div>
            </div>
        </AppLayout>
    );
}
