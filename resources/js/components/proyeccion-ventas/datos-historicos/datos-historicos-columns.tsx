'use client';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DataTableColumnHeader } from '@/components/ui/data-table-column-header';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { DatoVentaHistorico } from '@/types/proyeccion-ventas';
import { ColumnDef } from '@tanstack/react-table';
import { MoreHorizontal, Pencil, Trash2 } from 'lucide-react';
import { EditDatoHistoricoDialog } from './edit-dato-historico-dialog';

const MESES = [
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre',
];

interface ColumnOptions {
    empresaId: number;
    permissions: {
        canEdit: boolean;
        canDelete: boolean;
    };
    onEdit?: () => void;
    onDelete?: (id: number) => void;
}

export const getColumns = ({
    empresaId,
    permissions,
    onEdit,
    onDelete,
}: ColumnOptions): ColumnDef<DatoVentaHistorico>[] => [
    {
        id: 'select',
        header: ({ table }) => (
            <Checkbox
                checked={table.getIsAllPageRowsSelected()}
                onCheckedChange={(value) =>
                    table.toggleAllPageRowsSelected(!!value)
                }
                aria-label="Seleccionar todo"
            />
        ),
        cell: ({ row }) => (
            <Checkbox
                checked={row.getIsSelected()}
                onCheckedChange={(value) => row.toggleSelected(!!value)}
                aria-label="Seleccionar fila"
            />
        ),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: 'anio',
        header: ({ column }) => (
            <DataTableColumnHeader column={column} title="Año" />
        ),
        cell: ({ row }) => (
            <div className="font-medium">{row.getValue('anio')}</div>
        ),
        filterFn: (row, id, value) => {
            const rowValue = String(row.getValue(id));
            return rowValue.includes(String(value));
        },
    },
    {
        accessorKey: 'mes',
        header: ({ column }) => (
            <DataTableColumnHeader column={column} title="Mes" />
        ),
        cell: ({ row }) => {
            const mes = row.getValue('mes') as number;
            return <div>{MESES[mes - 1] || mes}</div>;
        },
        filterFn: (row, id, value) => {
            return value.includes(row.getValue(id));
        },
    },
    {
        accessorKey: 'monto',
        header: ({ column }) => (
            <div className="text-center">
                <DataTableColumnHeader column={column} title="Monto" />
            </div>
        ),
        cell: ({ row }) => {
            const monto = parseFloat(row.getValue('monto'));
            const formatted = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
            }).format(monto);

            return <div className="text-left font-medium">{formatted}</div>;
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const dato = row.original;

            return (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-8 w-8 p-0">
                            <span className="sr-only">Abrir menú</span>
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuLabel>Acciones</DropdownMenuLabel>
                        {permissions.canEdit && (
                            <EditDatoHistoricoDialog
                                dato={dato}
                                empresaId={empresaId}
                                onSuccess={onEdit}
                            >
                                <DropdownMenuItem
                                    onSelect={(e) => e.preventDefault()}
                                >
                                    <Pencil className="mr-2 h-4 w-4" /> Editar
                                </DropdownMenuItem>
                            </EditDatoHistoricoDialog>
                        )}
                        {permissions.canDelete && (
                            <>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    className="text-red-600"
                                    onClick={() =>
                                        onDelete && onDelete(dato.id)
                                    }
                                >
                                    <Trash2 className="mr-2 h-4 w-4" /> Eliminar
                                </DropdownMenuItem>
                            </>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
