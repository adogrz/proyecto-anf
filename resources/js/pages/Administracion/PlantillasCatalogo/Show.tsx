import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import CuentaNode, { type CuentaBaseNode } from '@/components/CuentaNode';

// Definiendo la interfaz para la plantilla y el árbol de cuentas
interface Plantilla {
    id: number;
    nombre: string;
    descripcion: string;
}

interface ShowProps {
    plantilla: Plantilla;
    cuentas_base_tree: CuentaBaseNode[];
}

export default function PlantillasCatalogoShow({ plantilla, cuentas_base_tree = [] }: ShowProps) {
    return (
        <AppLayout>
            <Head title={`Ver Plantilla: ${plantilla.nombre}`} />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold">{plantilla.nombre}</h1>
                    <p className="text-gray-600 mt-1">{plantilla.descripcion}</p>
                </div>
                
                <div className="shadow-md rounded-lg p-6">
                    <h2 className="text-xl font-semibold mb-4 border-b pb-2">Estructura de Cuentas</h2>
                    <div className="mt-4">
                        {cuentas_base_tree.length > 0 ? (
                            cuentas_base_tree.map((rootNode) => (
                                <CuentaNode key={rootNode.id} node={rootNode} level={0} />
                            ))
                        ) : (
                            <p className="text-center text-gray-500">Esta plantilla aún no tiene cuentas base asociadas.</p>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}