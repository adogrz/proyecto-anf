import React from 'react';

// Definición de la estructura de un nodo de cuenta
export interface CuentaBaseNode {
    id: number;
    codigo: string;
    nombre: string;
    tipo_cuenta: 'AGRUPACION' | 'DETALLE';
    children?: CuentaBaseNode[];
}

interface CuentaNodeProps {
    node: CuentaBaseNode;
    level: number;
}

const CuentaNode: React.FC<CuentaNodeProps> = ({ node, level }) => {
    const indent = level * 20; // 20px de indentación por nivel

    return (
        <div>
            <div className={`flex items-center py-1 hover:bg-gray-100 rounded`} style={{ paddingLeft: `${indent}px` }}>
                <span className="font-mono text-sm text-gray-600 w-32 flex-shrink-0">{node.codigo}</span>
                <span className={`text-sm ${node.tipo_cuenta === 'AGRUPACION' ? 'font-semibold' : ''}`}>{node.nombre}</span>
            </div>
            {node.children && node.children.length > 0 && (
                <div>
                    {node.children.map((child) => (
                        <CuentaNode key={child.id} node={child} level={level + 1} />
                    ))}
                </div>
            )}
        </div>
    );
};

export default CuentaNode;
