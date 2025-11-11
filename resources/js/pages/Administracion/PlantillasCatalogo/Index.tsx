
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-react';
import { columns, Plantilla } from './columns';
import { DataTable } from '@/components/ui/data-table';
import { type BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';
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
    plantillas: Plantilla[];
    breadcrumbs?: BreadcrumbItem[];
}

export default function PlantillasCatalogoIndex({ plantillas, breadcrumbs }: IndexProps) {
    const [showAlertDialog, setShowAlertDialog] = useState(false);
    const [itemToDelete, setItemToDelete] = useState<Plantilla | null>(null);
    const [itemNameToDelete, setItemNameToDelete] = useState('');

    const handleDeleteClick = (item: Plantilla) => {
        setItemToDelete(item);
        setItemNameToDelete(item.nombre);
        setShowAlertDialog(true);
    };

    const handleConfirmDelete = () => {
        if (itemToDelete) {
            router.delete(route('plantillas-catalogo.destroy', { plantilla_catalogo: itemToDelete.id }), {
                onSuccess: () => {
                    toast.success('Plantilla eliminada', {
                        description: `La plantilla "${itemNameToDelete}" ha sido eliminada correctamente.`,
                    });
                    setShowAlertDialog(false);
                    setItemToDelete(null);
                    setItemNameToDelete('');
                },
                onError: (errors) => {
                    const errorMessage = errors.error || 'No se pudo eliminar la plantilla.';
                    toast.error('Error al eliminar plantilla', {
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
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Plantillas de Catálogo" />
            <div className="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold">Gestión de Plantillas de Catálogo</h1>
                    <Button asChild>
                        <Link href={route('plantillas-catalogo.create')}>
                            <Plus className="mr-2 h-4 w-4" />
                            Crear Plantilla
                        </Link>
                    </Button>
                </div>

                <div className="shadow-md rounded-lg p-6">
                    <DataTable 
                        columns={columns(handleDeleteClick)} 
                        data={plantillas} 
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
                            Esta acción no se puede deshacer. Esto eliminará permanentemente la plantilla "{itemNameToDelete}" y todos sus datos asociados.
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