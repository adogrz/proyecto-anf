import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { route } from 'ziggy-js';
import { columns, Empresa } from './columns';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { toast } from 'sonner';

// Definiendo los props del componente
interface IndexProps {
    empresas: Empresa[];
}

const BREADCRUMBS: BreadcrumbItem[] = [
    { title: 'Home', href: route('dashboard') },
    { title: 'Empresas', href: route('empresas.index') },
];

export default function EmpresasIndex({ empresas }: IndexProps) {
    const [showAlertDialog, setShowAlertDialog] = useState(false);
    const [itemToDelete, setItemToDelete] = useState<Empresa | null>(null);
    const [itemNameToDelete, setItemNameToDelete] = useState('');

    const handleDeleteClick = (item: Empresa) => {
        setItemToDelete(item);
        setItemNameToDelete(item.nombre);
        setShowAlertDialog(true);
    };

    const handleConfirmDelete = () => {
        if (itemToDelete) {
            router.delete(route('empresas.destroy', { empresa: itemToDelete.id }), {
                onSuccess: () => {
                    toast.success('Empresa eliminada', {
                        description: `La empresa "${itemNameToDelete}" ha sido eliminada correctamente.`,
                    });
                    setShowAlertDialog(false);
                    setItemToDelete(null);
                    setItemNameToDelete('');
                },
                onError: (errors) => {
                    const errorMessage = errors.error || 'No se pudo eliminar la empresa.';
                    toast.error('Error al eliminar empresa', {
                        description: errorMessage,
                    });
                    setShowAlertDialog(false);
                    setItemToDelete(null);
                    setItemNameToDelete('');
                },
            });
        }
    };

    return (
        <AppLayout breadcrumbs={BREADCRUMBS}>
            <Head title="Gestión de Empresas" />
            <div className="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Gestión de Empresas</h1>
                    <Button asChild>
                        <Link href={route('empresas.create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            Crear Empresa
                        </Link>
                    </Button>
                </div>

                <div className="rounded-lg p-6 shadow-md">
                    <DataTable
                        columns={columns(handleDeleteClick)}
                        data={empresas}
                        filterColumn="nombre"
                        filterPlaceholder="Filtrar por nombre..."
                    />
                </div>
            </div>

            <AlertDialog open={showAlertDialog} onOpenChange={setShowAlertDialog}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Estás absolutamente seguro?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción no se puede deshacer. Esto eliminará permanentemente la empresa "{itemNameToDelete}" y todos sus datos asociados.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction onClick={handleConfirmDelete} className="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                            Eliminar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
