
import { ColumnDef } from "@tanstack/react-table"
import { MoreHorizontal, Pencil, Trash2 } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Link } from "@inertiajs/react"
import { DataTableColumnHeader } from "@/components/ui/data-table-column-header"

export interface Ratio {
    id: number;
    nombre_ratio: string;
    valor: number;
    tipo_ratio: string;
}

export const columns: ColumnDef<Ratio>[] = [
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
    accessorKey: "nombre_ratio",
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Nombre del Ratio" />
    ),
  },
  {
    accessorKey: "valor",
    header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Valor" />
    ),
  },
  {
    accessorKey: "tipo_ratio",
    header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Tipo" />
    ),
  },
  {
    id: "actions",
    cell: ({ row }) => {
      const ratio = row.original

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
              <Link href={route('ratios.edit', ratio.id)}><Pencil className="mr-2 h-4 w-4" /> Editar</Link>
            </DropdownMenuItem>
            <DropdownMenuItem className="text-red-600" asChild>
              <Link href={route('ratios.destroy', ratio.id)} method="delete" as="button">
                <Trash2 className="mr-2 h-4 w-4" /> Eliminar
              </Link>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      )
    },
  },
]
