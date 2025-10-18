import { ColumnDef } from "@tanstack/react-table"
import { DataTableColumnHeader } from "@/components/ui/data-table-column-header"
import { Checkbox } from "@/components/ui/checkbox"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Button } from "@/components/ui/button"
import { MoreHorizontal } from "lucide-react"
import { Link } from "@inertiajs/react"

export interface CuentaBase {
    id: number;
    codigo: string;
    nombre: string;
    tipo_cuenta: string;
    naturaleza: string;
    plantilla_catalogo_id: number;
    plantilla_catalogo?: {
        nombre: string;
    };
    parent?: {
        nombre: string;
    };
}

export const columns: ColumnDef<CuentaBase>[] = [
  {
    id: "select",
    header: ({ table }) => (
      <Checkbox
        checked={
          table.getIsAllPageRowsSelected() ||
          (table.getIsSomePageRowsSelected() && "indeterminate")
        }
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
    accessorKey: "codigo",
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Código" />
    ),
  },
  {
    accessorKey: "nombre",
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title="Nombre" />
    ),
  },
  {
    accessorKey: "tipo_cuenta",
    header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Tipo" />
    ),
  },
  {
    accessorKey: "naturaleza",
    header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Naturaleza" />
    ),
  },
  {
    accessorKey: "plantilla_catalogo.nombre",
    header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Plantilla" />
    ),
    cell: ({ row }) => {
        const cuentaBase = row.original;
        return cuentaBase.plantilla_catalogo?.nombre || 'N/A';
    }
  },
  {
    accessorKey: "parent.nombre",
    header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Cuenta Padre" />
    ),
    cell: ({ row }) => {
        const cuentaBase = row.original;
        return cuentaBase.parent?.nombre || 'N/A';
    }
  },
  {
    id: "actions",
    cell: ({ row }) => {
      const cuentaBase = row.original

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
            <DropdownMenuItem
              onClick={() => navigator.clipboard.writeText(cuentaBase.id.toString())}
            >
              Copiar ID
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link href={route('cuentas-base.edit', cuentaBase.id)}>Editar</Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
                <Link href={route('cuentas-base.destroy', cuentaBase.id)} method="delete" as="button">Eliminar</Link>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      )
    },
  },
]