
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
import { Badge } from "@/components/ui/badge"
import { Empresa } from "@/types"

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
        id: number;
        nombre: string;
    };
}

interface ColumnsProps {
    empresa: Empresa;
    handleDeleteClick: (cuentaBase: CuentaBase) => void;
}

export const columns = ({ empresa, handleDeleteClick }: ColumnsProps): ColumnDef<CuentaBase>[] => [
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
    cell: ({ row }) => {
        const tipo = row.getValue("tipo_cuenta") as string;
        return (
            <Badge variant={tipo === "AGRUPACION" ? "blue" : "green"}>
                {tipo}
            </Badge>
        );
    },
  },
  {
    accessorKey: "naturaleza",
    header: ({ column }) => (
        <DataTableColumnHeader column={column} title="Naturaleza" />
    ),
    cell: ({ row }) => {
        const naturaleza = row.getValue("naturaleza") as string;
        return (
            <Badge variant={naturaleza === "DEUDORA" ? "red" : "yellow"}>
                {naturaleza}
            </Badge>
        );
    },
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
                <Link href={route('empresas.cuentas-base.edit', { empresa: empresa.id, cuentas_base: cuentaBase.id })}>Editar</Link>
            </DropdownMenuItem>
            <DropdownMenuItem
                onClick={() => handleDeleteClick(cuentaBase)}
                className="text-red-600"
            >
                Eliminar
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      )
    },
  },
]