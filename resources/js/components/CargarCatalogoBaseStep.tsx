import React from 'react';
import { router } from '@inertiajs/react'; // Although router is not used directly, it's good to keep if related actions might be added
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Progress } from '@/components/ui/progress';
import { AlertCircle, AlertTriangle, FileUp, FileDown } from 'lucide-react';
import { toast } from 'sonner';

// Assuming Empresa is defined in '@/types' and will be imported
import { Empresa, CuentaBase } from '@/types';

// Helper components for badges - if these are commonly used, they should be in a separate utility file or component
const NatureBadge = ({ nature }: { nature: string }) => {
  const natureClass = nature === 'DEUDORA' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800';
  return <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${natureClass}`}>{nature}</span>;
};

const TypeBadge = ({ type }: { type: string }) => {
  const typeClass = type === 'AGRUPACION' ? 'bg-yellow-100 text-yellow-800' : 'bg-purple-100 text-purple-800';
  return <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${typeClass}`}>{type}</span>;
};


interface CargarCatalogoBaseStepProps {
  empresa: Empresa; // This should be the 'selected plantilla' owner
  onCatalogoBaseCargado: () => void;
  onBack?: () => void; // Optional if this step is the starting point in a modal
}

const CargarCatalogoBaseStep: React.FC<CargarCatalogoBaseStepProps> = ({ empresa, onCatalogoBaseCargado, onBack }) => {
  const [archivo, setArchivo] = React.useState<File | null>(null);
  const [previewData, setPreviewData] = React.useState<any[]>([]);
  const [isProcessing, setIsProcessing] = React.useState(false);
  const [uploadProgress, setUploadProgress] = React.useState(0);
  const [isDragging, setIsDragging] = React.useState(false);
  const [errors, setErrors] = React.useState<string[]>([]);
  const [warnings, setWarnings] = React.useState<string[]>([]);

  // State for filters and pagination
  const [nameFilter, setNameFilter] = React.useState('');
  const [natureFilter, setNatureFilter] = React.useState('all');
  const [typeFilter, setTypeFilter] = React.useState('all');
  const [currentPage, setCurrentPage] = React.useState(1);
  const itemsPerPage = 10;

  const handleFileSelect = (file: File | null) => {
    if (file) {
        const allowedExtensions = /(\.xlsx|\.xls|\.csv)$/i;
        if (!allowedExtensions.exec(file.name)) {
            toast.error('Tipo de archivo no válido', {
                description: 'Por favor, seleccione un archivo de Excel (.xlsx, .xls) o CSV (.csv).',
            });
            return;
        }
        setArchivo(file);
        setErrors([]);
        setWarnings([]);
        setPreviewData([]);
        setCurrentPage(1);
    }
  };

  const handleDragEnter = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    setIsDragging(false);
  };

  const handleDragOver = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
  };

  const handleDrop = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    setIsDragging(false);
    const droppedFile = e.dataTransfer.files[0];
    if (droppedFile) {
        handleFileSelect(droppedFile);
    }
  };

  const handlePreview = async () => {
    if (!archivo) {
        toast.warning('Por favor, seleccione un archivo primero.');
        return;
    }
    setIsProcessing(true);
    setUploadProgress(0);
    setErrors([]);
    setWarnings([]);
    setPreviewData([]);
    toast.info('Previsualizando catálogo base...');

    const formData = new FormData();
    formData.append('archivo', archivo);

    try {
      const response = await axios.post(route('importacion.previsualizarCatalogoBase'), formData, {
        onUploadProgress: (progressEvent) => {
          const percentCompleted = Math.round((progressEvent.loaded * 100) / (progressEvent.total || 1));
          setUploadProgress(percentCompleted);
        }
      });

      const { datos, errores, warnings: backendWarnings } = response.data;

      if (errores && errores.length > 0) {
        setErrors(errores);
        toast.error('Se encontraron errores en el archivo del catálogo base.', { description: 'Revise el registro de errores para más detalles.' });
      }

      if (backendWarnings && backendWarnings.length > 0) {
        setWarnings(backendWarnings);
        toast.warning('Se encontraron advertencias en el archivo del catálogo base.', { description: 'Revise el registro de advertencias para más detalles.' });
      }

      if (datos && datos.length > 0) {
        setPreviewData(datos);
        if (!errores || errores.length === 0) {
            toast.success('Archivo previsualizado. Revise los datos inferidos.');
        } else {
            toast.warning('Archivo previsualizado con algunos errores. Revise los resultados.');
        }
      } else {
        if (!errores || errores.length === 0) {
            toast.warning('El archivo se procesó, pero no se encontraron cuentas para previsualizar.', { description: 'Puede que el archivo esté vacío o las cabeceras no sean correctas.' });
        }
      }
    } catch (error: any) {
        console.error(error);
        toast.error('Ocurrió un error inesperado al previsualizar el archivo.');
    }
    finally { setIsProcessing(false); }
  };

  const handleImport = async () => {
    if (!archivo) {
        toast.warning('Por favor, seleccione un archivo primero.');
        return;
    }
    setIsProcessing(true);
    setUploadProgress(0);
    toast.info('Importando catálogo base...');

    const formData = new FormData();
    formData.append('archivo', archivo);
    formData.append('plantilla_catalogo_id', empresa.plantilla_catalogo_id.toString());
    formData.append('empresa_id', empresa.id.toString()); // Add this line

    try {
      const response = await axios.post(route('importacion.importarCatalogoBase'), formData, {
        onUploadProgress: (progressEvent) => {
          const percentCompleted = Math.round((progressEvent.loaded * 100) / (progressEvent.total || 1));
          setUploadProgress(percentCompleted);
        }
      });

      if (response.data.warnings && response.data.warnings.length > 0) {
        setWarnings(response.data.warnings);
        toast.warning('Catálogo importado con advertencias.', { description: 'Algunas cuentas no se pudieron eliminar porque están en uso.' });
      } else {
        toast.success('Catálogo base importado con éxito.');
      }

      onCatalogoBaseCargado();
    } catch (error: any) {
        console.error(error);
        toast.error(error.response?.data?.message || 'Ocurrió un error al importar el catálogo base.');
    } finally {
      setIsProcessing(false);
    }
  };

  // Filtering and pagination logic
  const filteredData = previewData.filter(item => {
    return (
      (nameFilter === '' || item.nombre.toLowerCase().includes(nameFilter.toLowerCase())) &&
      (natureFilter === 'all' || item.naturaleza === natureFilter) &&
      (typeFilter === 'all' || item.tipo_cuenta === typeFilter)
    );
  });

  const paginatedData = filteredData.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

  const totalPages = Math.ceil(filteredData.length / itemsPerPage);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Cargar Catálogo Base de Cuentas</CardTitle>
        <CardDescription>Arrastre y suelte su catálogo base en formato Excel (.xlsx, .xls, .csv) en el área designada o haga clic para seleccionarlo. Este catálogo definirá la estructura estándar de cuentas para la empresa.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        <div className="flex items-center gap-4 flex-wrap">
            <Label
                htmlFor="catalogo-base-file-input"
                className={`flex-1 flex flex-col items-center justify-center w-full p-6 border-2 border-dashed rounded-md cursor-pointer transition-colors
                    ${isDragging ? 'border-primary bg-primary/10' : 'hover:bg-muted/50'}`}
                onDragEnter={handleDragEnter}
                onDragLeave={handleDragLeave}
                onDragOver={handleDragOver}
                onDrop={handleDrop}
            >
                <FileUp className={`w-10 h-10 mx-auto mb-2 ${isDragging ? 'text-primary' : 'text-muted-foreground'}`} />
                <p className="font-semibold">{archivo ? archivo.name : 'Arrastre un archivo aquí o haga clic para seleccionar'}</p>
                <p className="text-xs text-muted-foreground">Columnas requeridas: codigo_cuenta, nombre_cuenta</p>
            </Label>
            <Input
                id="catalogo-base-file-input"
                type="file"
                className="hidden"
                onChange={(e) => handleFileSelect(e.target.files ? e.target.files[0] : null)}
                accept=".xlsx,.xls,.csv"
            />
          <Button onClick={handlePreview} disabled={!archivo || isProcessing} size="lg">
            {isProcessing ? 'Previsualizando...' : 'Previsualizar Catálogo'}
          </Button>
          <Button variant="download" size="lg" asChild>
            <a href={route('importacion.descargarPlantilla', { tipo: 'catalogo' })}>
              <FileDown className="mr-2 h-4 w-4" /> Descargar Plantilla
            </a>
          </Button>
        </div>

        {isProcessing && uploadProgress > 0 && <Progress value={uploadProgress} className="w-full" />}

        {errors.length > 0 && (
            <div className="space-y-2 pt-4">
                <Label className="text-destructive flex items-center gap-2">
                  <AlertCircle className="h-4 w-4" /> Registro de Errores de Catálogo Base
                </Label>
                <div className="bg-destructive/10 border border-destructive/20 rounded-md p-3 max-h-40 overflow-y-auto">
                    <pre className="text-sm text-destructive whitespace-pre-wrap font-mono">
                        {errors.map((error, i) => (
                            <p key={i} className="flex items-start">
                                <span className="mr-2 text-red-400">-&gt;</span>
                                <span>{error}</span>
                            </p>
                        ))}
                    </pre>
                </div>
            </div>
        )}

        {warnings.length > 0 && (
            <div className="space-y-2 pt-4">
                <Label className="text-orange-500 flex items-center gap-2">
                  <AlertTriangle className="h-4 w-4" /> Registro de Advertencias de Catálogo Base
                </Label>
                <div className="bg-yellow-100/10 border border-yellow-200/20 rounded-md p-3 max-h-40 overflow-y-auto">
                    <pre className="text-sm text-yellow-700 whitespace-pre-wrap font-mono">
                        {warnings.map((warning, i) => (
                            <p key={i} className="flex items-start">
                                <span className="mr-2 text-yellow-500">-&gt;</span>
                                <span>{warning}</span>
                            </p>
                        ))}
                    </pre>
                </div>
            </div>
        )}

        {previewData.length > 0 && (
          <div>
            <h3 className="text-md font-medium mb-4">Previsualización del Catálogo Base</h3>
            <div className="flex items-center gap-4 mb-4">
                <Input placeholder="Filtrar por nombre..." value={nameFilter} onChange={e => setNameFilter(e.target.value)} className="max-w-sm" />
                <div className="flex items-center space-x-2">
                    <Label htmlFor="nature-filter">Naturaleza:</Label>
                    <Select value={natureFilter} onValueChange={setNatureFilter}>
                        <SelectTrigger id="nature-filter" className="w-[180px]">
                            <SelectValue placeholder="Filtrar por naturaleza" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas</SelectItem>
                            <SelectItem value="DEUDORA">Deudora</SelectItem>
                            <SelectItem value="ACREEDORA">Acreedora</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div className="flex items-center space-x-2">
                    <Label htmlFor="type-filter">Tipo:</Label>
                    <Select value={typeFilter} onValueChange={setTypeFilter}>
                        <SelectTrigger id="type-filter" className="w-[180px]">
                            <SelectValue placeholder="Filtrar por tipo" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos</SelectItem>
                            <SelectItem value="AGRUPACION">Agrupación</SelectItem>
                            <SelectItem value="DETALLE">Detalle</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <div className="border rounded-md">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Código</TableHead>
                    <TableHead>Nombre</TableHead>
                    <TableHead>Naturaleza (Inferida)</TableHead>
                    <TableHead>Tipo (Inferido)</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {paginatedData.map((cuenta, index) => (
                    <TableRow key={index}>
                      <TableCell className="font-medium">{cuenta.codigo}</TableCell>
                      <TableCell>{cuenta.nombre}</TableCell>
                      <TableCell><NatureBadge nature={cuenta.naturaleza} /></TableCell>
                      <TableCell><TypeBadge type={cuenta.tipo_cuenta} /></TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            <div className="flex items-center justify-end space-x-2 py-4">
                <Button variant="outline" size="sm" onClick={() => setCurrentPage(p => Math.max(1, p - 1))} disabled={currentPage === 1}>Anterior</Button>
                <span className="text-sm">Página {currentPage} de {totalPages}</span>
                <Button variant="outline" size="sm" onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))} disabled={currentPage === totalPages}>Siguiente</Button>
            </div>
          </div>
        )}
      </CardContent>
      <CardFooter className="flex justify-between">
        {onBack && <Button variant="outline" onClick={onBack}>Atrás</Button>}
        <Button onClick={handleImport} disabled={isProcessing || previewData.length === 0 || errors.length > 0}>
          {isProcessing ? 'Importando...' : 'Confirmar e Importar Catálogo Base'}
        </Button>
      </CardFooter>
    </Card>
  );
};

export default CargarCatalogoBaseStep;
