import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { Link, router } from '@inertiajs/react'; // Import router
import { route } from 'ziggy-js';
import { Badge } from '@/components/ui/badge';
import { ArrowUpDown } from 'lucide-react';

// Interfaces
interface Empresa {
    id: number;
    nombre: string;
}

export interface EstadoFinanciero {
    id: number;
    empresa_id: number;
    anio: number;
    tipo_estado: 'balance_general' | 'estado_resultados';
    created_at: string;
    updated_at: string;
}

export const columns: ColumnDef<EstadoFinanciero>[] = [
    {
        accessorKey: 'anio',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
                >
                    Año
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => <div className="font-medium">{row.getValue('anio')}</div>,
    },
    {
        accessorKey: 'tipo_estado',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
                >
                    Tipo de Estado
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            const tipo = row.original.tipo_estado;
            let variant: 'default' | 'secondary' | 'destructive' | 'outline' | 'blue' | 'green' | 'red' | 'yellow' = 'secondary';
            let displayTipo = '';

            if (tipo === 'balance_general') {
                variant = 'blue';
                displayTipo = 'Balance General';
            } else if (tipo === 'estado_resultados') {
                variant = 'green';
                displayTipo = 'Estado de Resultados';
            }
            return tipo ? <Badge variant={variant}>{displayTipo}</Badge> : 'N/A';
        },
    },
    {
        accessorKey: 'created_at',
        header: 'Fecha de Creación',
        cell: ({ row }) => {
            const date = new Date(row.getValue('created_at'));
            return <div>{date.toLocaleDateString()}</div>;
        },
    },
    {
        id: 'actions',
        header: 'Acciones',
        cell: ({ row }) => {
            const empresaId = row.original.empresa_id;
            const estadoFinancieroId = row.original.id;

            const handleDelete = () => {
                if (confirm('¿Estás seguro de que quieres eliminar este estado financiero? Esta acción no se puede deshacer.')) {
                    router.delete(route('empresas.estados-financieros.destroy', { empresa: empresaId, estados_financiero: estadoFinancieroId }));
                }
            };

            return (
                <div className="space-x-2">
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('empresas.estados-financieros.show', { empresa: empresaId, estados_financiero: estadoFinancieroId })}>
                            Ver
                        </Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('empresas.estados-financieros.edit', { empresa: empresaId, estados_financiero: estadoFinancieroId })}>
                            Editar
                        </Link>
                    </Button>
                    <Button variant="destructive" size="sm" onClick={handleDelete}>
                        Eliminar
                    </Button>
                </div>
            );
        },
    },
];