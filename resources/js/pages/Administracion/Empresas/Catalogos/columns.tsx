import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { Badge } from '@/components/ui/badge';
import { ArrowUpDown } from 'lucide-react';

// Interfaces
interface Empresa {
    id: number;
    nombre: string;
}

interface CuentaBase {
    id: number;
    nombre: string;
    codigo: string;
    tipo_cuenta: 'AGRUPACION' | 'DETALLE';
    naturaleza: 'DEUDORA' | 'ACREEDORA';
}

export interface CatalogoCuenta {
    id: number;
    empresa_id: number;
    codigo_cuenta: string;
    nombre_cuenta: string;
    cuenta_base_id: number | null;
    cuenta_base?: CuentaBase; // Eager loaded relationship
}

export const columns: ColumnDef<CatalogoCuenta>[] = [
    {
        accessorKey: 'codigo_cuenta',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
                >
                    Código
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => <div className="font-medium">{row.getValue('codigo_cuenta')}</div>,
    },
    {
        accessorKey: 'nombre_cuenta',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
                >
                    Nombre
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => <div>{row.getValue('nombre_cuenta')}</div>,
    },
    {
        accessorKey: 'cuenta_base.nombre',
        header: 'Cuenta Base',
        cell: ({ row }) => <div>{row.original.cuenta_base?.nombre || 'N/A'}</div>,
    },
    {
        accessorKey: 'cuenta_base.tipo_cuenta',
        header: 'Tipo',
        cell: ({ row }) => {
            const tipo = row.original.cuenta_base?.tipo_cuenta;
            let variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'blue' | 'green' | 'red' | 'yellow' = 'secondary';
            if (tipo === 'AGRUPACION') {
                variant = 'blue';
            } else if (tipo === 'DETALLE') {
                variant = 'green';
            }
            return tipo ? <Badge variant={variant}>{tipo}</Badge> : 'N/A';
        },
    },
    {
        accessorKey: 'cuenta_base.naturaleza',
        header: 'Naturaleza',
        cell: ({ row }) => {
            const naturaleza = row.original.cuenta_base?.naturaleza;
            let variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'blue' | 'green' | 'red' | 'yellow' = 'secondary';
            if (naturaleza === 'DEUDORA') {
                variant = 'red';
            } else if (naturaleza === 'ACREEDORA') {
                variant = 'green';
            }
            return naturaleza ? <Badge variant={variant}>{naturaleza}</Badge> : 'N/A';
        },
    },
    {
        id: 'actions',
        header: 'Acciones',
        cell: ({ row }) => {
            const empresaId = row.original.empresa_id;
            const catalogoCuentaId = row.original.id;
            return (
                <div className="space-x-2">
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('empresas.catalogos.edit', { empresa: empresaId, catalogo: catalogoCuentaId })}>
                            Editar
                        </Link>
                    </Button>
                    {/* Add Delete button here if needed */}
                </div>
            );
        },
    },
];
