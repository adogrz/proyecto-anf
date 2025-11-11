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
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    BookText,
    Eye,
    FileText,
    MoreHorizontal,
    Pencil,
    Trash2,
    TrendingUp,
    LineChart,
    BarChart3,
} from 'lucide-react';
import { route } from 'ziggy-js';

// Interfaces
interface Sector {
    id: number;
    nombre: string;
}

interface PlantillaCatalogo {
    id: number;
    nombre: string;
}

export interface Empresa {
    id: number;
    nombre: string;
    sector: Sector;
    plantilla_catalogo: PlantillaCatalogo | null;
}

export const columns: ColumnDef<Empresa>[] = [
    {
        id: 'select',
        header: ({ table }) => (
            <Checkbox
                checked={table.getIsAllPageRowsSelected()}
                onCheckedChange={(value) =>
                    table.toggleAllPageRowsSelected(!!value)
                }
                aria-label="Select all"
            />
        ),
        cell: ({ row }) => (
            <Checkbox
                checked={row.getIsSelected()}
                onCheckedChange={(value) => row.toggleSelected(!!value)}
                aria-label="Select row"
            />
        ),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: 'nombre',
        header: ({ column }) => (
            <DataTableColumnHeader column={column} title="Nombre" />
        ),
    },
    {
        accessorKey: 'sector.nombre',
        header: ({ column }) => (
            <DataTableColumnHeader column={column} title="Sector" />
        ),
        cell: ({ row }) => row.original.sector.nombre,
    },
    {
        accessorKey: 'plantilla_catalogo.nombre',
        header: ({ column }) => (
            <DataTableColumnHeader column={column} title="Plantilla" />
        ),
        cell: ({ row }) =>
            row.original.plantilla_catalogo?.nombre || 'No asignada',
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const empresa = row.original;

            return (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-8 w-8 p-0">
                            <span className="sr-only">Open menu</span>
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuLabel>Acciones</DropdownMenuLabel>
                        <DropdownMenuItem asChild>
                            <Link href={route('empresas.show', empresa.id)}>
                                <Eye className="mr-2 h-4 w-4" /> Ver
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={route('empresas.edit', empresa.id)}>
                                <Pencil className="mr-2 h-4 w-4" /> Editar
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link
                                href={route(
                                    'dashboard.proyecciones',
                                    empresa.id,
                                )}
                            >
                                <TrendingUp className="mr-2 h-4 w-4" /> Gestión
                                de Datos Históricos
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link
                                href={route('empresas.catalogos.index', {
                                    empresa: empresa.id,
                                })}
                            >
                                <BookText className="mr-2 h-4 w-4" /> Catálogo
                                de Cuentas
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link
                                href={route(
                                    'empresas.estados-financieros.index',
                                    { empresa: empresa.id },
                                )}
                            >
                                <FileText className="mr-2 h-4 w-4" /> Estados
                                Financieros
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={route('analisis.index', { empresa: empresa.id })}>
                              <BarChart3 className="mr-2 h-4 w-4" /> Análisis Comparativo</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={route('analisis.grafico-variaciones', { empresa: empresa.id })}>
                              <LineChart className="mr-2 h-4 w-4" /> Gráfico de Variaciones</Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem className="text-red-600" asChild>
                            <Link
                                href={route('empresas.destroy', empresa.id)}
                                method="delete"
                                as="button"
                            >
                                <Trash2 className="mr-2 h-4 w-4" /> Eliminar
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
