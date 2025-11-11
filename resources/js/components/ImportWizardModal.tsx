import React from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import Wizard from '@/pages/Importacion/Wizard'; // Assuming this is the path to the wizard component

interface PlantillaCatalogo {
    id: number;
    nombre: string;
}

interface ImportWizardModalProps {
    plantilla: PlantillaCatalogo;
    isOpen: boolean;
    onClose: () => void;
}

export default function ImportWizardModal({ plantilla, isOpen, onClose }: ImportWizardModalProps) {
    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[900px] h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>Importar Catálogo y Estados Financieros para {plantilla.nombre}</DialogTitle>
                </DialogHeader>
                <div className="flex-grow overflow-auto">
                    <Wizard
                        isModal={true}
                        initialPlantillaId={plantilla.id}
                        onImportSuccess={onClose}
                    />
                </div>
            </DialogContent>
        </Dialog>
    );
}
