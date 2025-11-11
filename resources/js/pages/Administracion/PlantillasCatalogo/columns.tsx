
import { ColumnDef } from "@tanstack/react-table"
import { MoreHorizontal, Pencil, Trash2, Eye, FileCog } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Link } from "@inertiajs/react"
import { DataTableColumnHeader } from "@/components/ui/data-table-column-header"

export interface Plantilla {
    id: number;
    nombre: string;
    descripcion: string;
}

export const columns: ColumnDef<Plantilla>[] = [
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
    accessorKey: "nombre",
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Nombre" />
    ),
  },
  {
    accessorKey: "descripcion",
    header: "Descripción",
  },
  {
    id: "actions",
    cell: ({ row }) => {
      const plantilla = row.original

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
              <Link href={route('cuentas-base.index', { plantilla: plantilla.id })}>
                <FileCog className="mr-2 h-4 w-4" /> Administrar Cuentas
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link href={route('plantillas-catalogo.show', { plantillas_catalogo: plantilla.id })}><Eye className="mr-2 h-4 w-4" /> Ver</Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link href={route('plantillas-catalogo.edit', plantilla.id)}><Pencil className="mr-2 h-4 w-4" /> Editar</Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem className="text-red-600" asChild>
              <Link href={route('plantillas-catalogo.destroy', plantilla.id)} method="delete" as="button">
                <Trash2 className="mr-2 h-4 w-4" /> Eliminar
              </Link>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      )
    },
  },
]
