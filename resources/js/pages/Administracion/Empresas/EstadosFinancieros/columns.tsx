
import { ColumnDef } from "@tanstack/react-table"
import { MoreHorizontal, Eye } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Link } from "@inertiajs/react"
import { DataTableColumnHeader } from "@/components/ui/data-table-column-header"

export interface EstadoFinanciero {
    id: number;
    anio: number;
    tipo_estado: string;
}

const formatTipoEstado = (tipo: string) => {
    return tipo.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
};

export const columns: ColumnDef<EstadoFinanciero>[] = [
  {
    id: "select",
    header: ({ table }) => (
      <Checkbox
        checked={table.getIsAllPageRowsSelected()}
        onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
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
    accessorKey: "anio",
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Año" />
    ),
  },
  {
    accessorKey: "tipo_estado",
    header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Tipo de Estado" />
    ),
    cell: ({ row }) => formatTipoEstado(row.original.tipo_estado),
  },
  {
    id: "actions",
    cell: ({ row }) => {
      const estado = row.original

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
              <Link href={route('estados-financieros.show', estado.id)}><Eye className="mr-2 h-4 w-4" /> Ver Detalles</Link>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      )
    },
  },
]
