// resources/js/components/import/ImportSuccessModal.tsx
import React from 'react';
import { router } from '@inertiajs/react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { CheckCircle } from 'lucide-react';

interface ImportSuccessModalProps {
  isOpen: boolean;
  onClose: () => void;
  message: string;
  empresaId: number | null;
}

const ImportSuccessModal: React.FC<ImportSuccessModalProps> = ({ isOpen, onClose, message, empresaId }) => {
  const handleContinueImporting = () => {
    onClose(); // Close modal
    router.visit(route('importacion.wizard')); // Reset wizard to step 1
  };

  const handleGoToCompanies = () => {
    onClose(); // Close modal
    router.visit(route('empresas.index')); // Go to companies index
  };

  const handleGoToCompanyDetails = () => {
    onClose(); // Close modal
    if (empresaId) {
      router.visit(route('empresas.show', empresaId)); // Go to specific company details
    } else {
      router.visit(route('empresas.index')); // Fallback to companies index
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <CheckCircle className="h-6 w-6 text-green-500" />
            Importación Exitosa
          </DialogTitle>
          <DialogDescription>
            {message}
          </DialogDescription>
        </DialogHeader>
        <div className="py-4">
          <p className="text-sm text-muted-foreground">¿Qué deseas hacer ahora?</p>
        </div>
        <DialogFooter className="flex flex-col sm:flex-row sm:flex-wrap sm:justify-end gap-2">
          <Button variant="outline" onClick={handleContinueImporting}>
            Seguir Importando
          </Button>
          {empresaId && (
            <Button variant="secondary" onClick={handleGoToCompanyDetails}>
              Ver Detalles de Empresa
            </Button>
          )}
          <Button onClick={handleGoToCompanies}>
            Ir a Empresas
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

export default ImportSuccessModal;
