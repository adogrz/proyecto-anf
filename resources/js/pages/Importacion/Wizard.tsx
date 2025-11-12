import AppLayout from '@/layouts/app-layout';
import React from 'react';
import { CuentaBase, Empresa, PlantillaCatalogo, Sector } from '@/types';
import { useForm, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import axios from 'axios';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Progress } from '@/components/ui/progress';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { FileUp, AlertCircle, AlertTriangle, FileDown } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { toast } from 'sonner';

// --- Componente para el Paso 1 ---
interface DefineEmpresaStepProps {
  empresas: Empresa[];
  sectores: Sector[];
  onEmpresaSelected: (empresa: Empresa, action: 'goToStep2' | 'goToStep3') => void;
}

const DefineEmpresaStep: React.FC<DefineEmpresaStepProps> = ({ empresas, sectores, onEmpresaSelected }) => {
  const [tipoSeleccion, setTipoSeleccion] = React.useState('existente');
  const [selectedEmpresa, setSelectedEmpresa] = React.useState<Empresa | null>(null);
  const [existingCompanyState, setExistingCompanyState] = React.useState<'idle' | 'loading' | 'has_catalog' | 'no_catalog'>('idle');

  const { data, setData, post, processing, errors } = useForm({
    nombre: '',
    sector_id: sectores[0]?.id.toString() || '',
    nombre_plantilla: '',
  });

  const handleExistingCompanySelect = async (empresaId: string) => {
    const selected = empresas.find(emp => emp.id.toString() === empresaId);
    if (selected) {
      setSelectedEmpresa(selected);
      setExistingCompanyState('loading');
      try {
        const response = await axios.get(route('empresas.checkCatalogStatus', selected.id));
        if (response.data.has_catalog) {
          setExistingCompanyState('has_catalog');
        } else {
          setExistingCompanyState('no_catalog');
        }
      } catch (error) {
        console.error('Error checking catalog status:', error);
        setExistingCompanyState('idle'); // Reset on error
      }
    }
  };

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('empresas.store'), {
      onSuccess: (page) => {
        const newEmpresa = (page.props as any).flash.empresa as Empresa;
        if (newEmpresa) {
          onEmpresaSelected(newEmpresa, 'goToStep2'); // New companies always go to step 2
        }
      },
      onError: (errors) => { // Add onError callback for debugging
        console.error('onError callback executed. Errors:', errors);
      }
    });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Paso 1: Definir la Empresa</CardTitle>
        <CardDescription>Seleccione una empresa existente para añadirle datos o cree una nueva desde cero.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        <RadioGroup defaultValue="existente" onValueChange={(value) => { setTipoSeleccion(value); setExistingCompanyState('idle'); setSelectedEmpresa(null); }} className="flex space-x-4">
          <div className="flex items-center space-x-2">
            <RadioGroupItem value="existente" id="existente" />
            <Label htmlFor="existente">Empresa Existente</Label>
          </div>
          <div className="flex items-center space-x-2">
            <RadioGroupItem value="nueva" id="nueva" />
            <Label htmlFor="nueva">Nueva Empresa</Label>
          </div>
        </RadioGroup>

        {tipoSeleccion === 'existente' ? (
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="empresa-existente">Seleccione una empresa</Label>
              <Select onValueChange={handleExistingCompanySelect} value={selectedEmpresa?.id.toString() || ''}>
                <SelectTrigger id="empresa-existente">
                  <SelectValue placeholder="-- Seleccione una empresa --" />
                </SelectTrigger>
                <SelectContent>
                  {empresas.map((empresa) => (
                    <SelectItem key={empresa.id} value={empresa.id.toString()}>{empresa.nombre}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {existingCompanyState === 'loading' && <p className="text-sm text-muted-foreground">Verificando catálogo...</p>}
            
            {existingCompanyState === 'has_catalog' && selectedEmpresa && (
              <Alert>
                <AlertCircle className="h-4 w-4" />
                <AlertTitle>Catálogo Encontrado</AlertTitle>
                <AlertDescription>
                  <p>Esta empresa ya tiene un catálogo de cuentas configurado. Puede importar estados financieros directamente o re-mapear el catálogo si es necesario.</p>
                  <div className="mt-4 flex space-x-4">
                    <Button onClick={() => onEmpresaSelected(selectedEmpresa, 'goToStep3')}>Importar Estado Financiero</Button>
                    <Button variant="outline" onClick={() => onEmpresaSelected(selectedEmpresa, 'goToStep2')}>Re-mapear Catálogo</Button>
                  </div>
                </AlertDescription>
              </Alert>
            )}

            {existingCompanyState === 'no_catalog' && selectedEmpresa && (
              <Alert variant="destructive">
                <AlertCircle className="h-4 w-4" />
                <AlertTitle>Acción Requerida</AlertTitle>
                <AlertDescription>
                  <p>Esta empresa no tiene un catálogo de cuentas. Debe cargar y mapear uno antes de continuar.</p>
                  <div className="mt-4">
                    <Button onClick={() => onEmpresaSelected(selectedEmpresa, 'goToStep2')}>Cargar Catálogo de Cuentas</Button>
                  </div>
                </AlertDescription>
              </Alert>
            )}
          </div>
        ) : (
          <form className="space-y-4" onSubmit={handleCreateSubmit}>
            <div className="space-y-2">
              <Label htmlFor="nombre">Nombre de la Empresa</Label>
              <Input id="nombre" value={data.nombre} onChange={e => setData('nombre', e.target.value)} placeholder="Ej. Mi Empresa S.A. de C.V." />
              {errors.nombre && <p className="text-sm text-red-600 mt-1">{errors.nombre}</p>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="nombre_plantilla">Nombre de la Plantilla de Catálogo</Label>
              <Input id="nombre_plantilla" value={data.nombre_plantilla} onChange={e => setData('nombre_plantilla', e.target.value)} placeholder="Ej. Catálogo NIIF Pymes 2024" />
              {errors.nombre_plantilla && <p className="text-sm text-red-600 mt-1">{errors.nombre_plantilla}</p>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="sector">Sector Industrial</Label>
              <Select onValueChange={value => setData('sector_id', value)} value={data.sector_id.toString()}>
                <SelectTrigger id="sector">
                  <SelectValue placeholder="Seleccione un sector" />
                </SelectTrigger>
                <SelectContent>
                  {sectores.map((sector) => (
                    <SelectItem key={sector.id} value={sector.id.toString()}>{sector.nombre}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <Button type="submit" disabled={processing}>
              {processing ? 'Creando...' : 'Crear y Continuar'}
            </Button>
          </form>
        )}
      </CardContent>
    </Card>
  );
};

// --- Componente para el Paso 2 (Cargar Catálogo Base) ---
interface CargarCatalogoBaseStepProps {
  empresa: Empresa;
  onCatalogoBaseCargado: () => void;
  onBack: () => void;
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

  const NatureBadge = ({ nature }: { nature: string }) => {
    const natureClass = nature === 'DEUDORA' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800';
    return <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${natureClass}`}>{nature}</span>;
  };

  const TypeBadge = ({ type }: { type: string }) => {
    const typeClass = type === 'AGRUPACION' ? 'bg-yellow-100 text-yellow-800' : 'bg-purple-100 text-purple-800';
    return <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${typeClass}`}>{type}</span>;
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Paso 2: Cargar Catálogo Base de Cuentas</CardTitle>
        <CardDescription>Arrastre y suelte su catálogo base en formato Excel (.xlsx, .xls, .csv) en el área designada o haga clic para seleccionarlo. Este catálogo definirá la estructura estándar de cuentas para la empresa.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        <div className="flex items-center gap-4 flex-wrap">
            <Label 
                htmlFor="catalogo-base-file-input" 
                className={`flex-1 flex items-center justify-center w-full p-6 border-2 border-dashed rounded-md cursor-pointer transition-colors 
                    ${isDragging ? 'border-primary bg-primary/10' : 'hover:bg-muted/50'}`}
                onDragEnter={handleDragEnter}
                onDragLeave={handleDragLeave}
                onDragOver={handleDragOver}
                onDrop={handleDrop}
            >
                <div className="text-center">
                    <FileUp className={`w-10 h-10 mx-auto mb-2 ${isDragging ? 'text-primary' : 'text-muted-foreground'}`} />
                    <p className="font-semibold">{archivo ? archivo.name : 'Arrastre un archivo aquí o haga clic para seleccionar'}</p>
                    <p className="text-xs text-muted-foreground">Columnas requeridas: codigo_cuenta, nombre_cuenta</p>
                </div>
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
          <Button variant="success" size="lg" asChild>
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
        <Button variant="outline" onClick={onBack}>Atrás</Button>
        <Button onClick={handleImport} disabled={isProcessing || previewData.length === 0 || errors.length > 0}>
          {isProcessing ? 'Importando...' : 'Confirmar e Importar Catálogo Base'}
        </Button>
      </CardFooter>
    </Card>
  );
};

// --- Componente para el Paso 2 (Mapeo de Catálogo Existente) ---
interface MapeoCatalogoStepProps {
  empresa: Empresa;
  cuentasBase: CuentaBase[];
  onMapeoCompleto: () => void;
  onBack: () => void;
}
const MapeoCatalogoStep: React.FC<MapeoCatalogoStepProps> = ({ empresa, cuentasBase, onMapeoCompleto, onBack }) => {
  const [archivo, setArchivo] = React.useState<File | null>(null);
  const [cuentas, setCuentas] = React.useState<any[]>([]);
  const [isProcessing, setIsProcessing] = React.useState(false);
  const [uploadProgress, setUploadProgress] = React.useState(0);
  const [isDragging, setIsDragging] = React.useState(false);
  const [errors, setErrors] = React.useState<string[]>([]);
  const [warnings, setWarnings] = React.useState<string[]>([]);

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
        setErrors([]); // Clear previous errors on new file select
        setCuentas([]); // Clear previous preview
    }
  }

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


  const handleAutomap = async () => {
    if (!archivo) {
        toast.warning('Por favor, seleccione un archivo primero.');
        return;
    }
    setIsProcessing(true);
    setUploadProgress(0);
    setErrors([]);
    setCuentas([]);
    toast.info('Procesando archivo, por favor espere...');

    const formData = new FormData();
    formData.append('archivo', archivo);
    formData.append('plantilla_catalogo_id', empresa.plantilla_catalogo_id.toString());
    
    try {
      const response = await axios.post(route('importacion.automap'), formData, {
        onUploadProgress: (progressEvent) => {
          const percentCompleted = Math.round((progressEvent.loaded * 100) / (progressEvent.total || 1));
          setUploadProgress(percentCompleted);
        }
      });

      const { datos, errores, warnings: backendWarnings } = response.data;

      if (errores && errores.length > 0) {
        setErrors(errores);
        toast.error('Se encontraron errores en el archivo del catálogo.', { description: 'Revise el registro de errores para más detalles.' });
      }

      if (backendWarnings && backendWarnings.length > 0) {
        setWarnings(backendWarnings);
        toast.warning('Se encontraron advertencias en el archivo del catálogo.', { description: 'Revise el registro de advertencias para más detalles.' });
      }

      if (datos && datos.length > 0) {
        setCuentas(datos);
        if (!errores || errores.length === 0) {
            toast.success('Archivo procesado. Revise la previsualización del mapeo.');
        } else {
            toast.warning('Archivo procesado con algunos errores. Revise los resultados.');
        }
      } else {
        if (!errores || errores.length === 0) {
            toast.warning('El mapeo se completó, pero no se encontraron cuentas para mostrar.', { description: 'Puede que el archivo esté vacío o las cabeceras no sean correctas.' });
        }
      }
    } catch (error: any) {
        console.error(error);
        toast.error('Ocurrió un error inesperado al procesar el archivo.');
    }
    finally { setIsProcessing(false); }
  };

  const handleMapeoChange = (index: number, cuentaBaseId: string) => {
    const nuevasCuentas = [...cuentas];
    if (cuentaBaseId === 'null') {
      nuevasCuentas[index].cuenta_base_id = null;
    } else {
      nuevasCuentas[index].cuenta_base_id = parseInt(cuentaBaseId);
    }
    setCuentas(nuevasCuentas);
  };

  const handleGuardarMapeo = async () => {
    setIsProcessing(true);
    toast.info('Guardando mapeo...');
    try {
      await axios.post(route('importacion.guardarMapeo'), {
        empresa_id: empresa.id,
        cuentas: cuentas,
      });
      toast.success('Mapeo guardado con éxito.');
      onMapeoCompleto();
    } catch (error) {
        console.error(error);
        toast.error('No se pudo guardar el mapeo.');
    }
    finally { setIsProcessing(false); }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Paso 2: Cargar y Mapear Catálogo de Cuentas</CardTitle>
        <CardDescription>Arrastre y suelte su catálogo en formato Excel (.xlsx, .xls, .csv) en el área designada o haga clic para seleccionarlo.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        <div className="flex items-center gap-4 flex-wrap">
            <Label 
                htmlFor="catalogo-file-input" 
                className={`flex-1 flex items-center justify-center w-full p-6 border-2 border-dashed rounded-md cursor-pointer transition-colors 
                    ${isDragging ? 'border-primary bg-primary/10' : 'hover:bg-muted/50'}`}
                onDragEnter={handleDragEnter}
                onDragLeave={handleDragLeave}
                onDragOver={handleDragOver}
                onDrop={handleDrop}
            >
                <div className="text-center">
                    <FileUp className={`w-10 h-10 mx-auto mb-2 ${isDragging ? 'text-primary' : 'text-muted-foreground'}`} />
                    <p className="font-semibold">{archivo ? archivo.name : 'Arrastre un archivo aquí o haga clic para seleccionar'}</p>
                    <p className="text-xs text-muted-foreground">Columnas requeridas: codigo_cuenta, nombre_cuenta</p>
                </div>
            </Label>
            <Input 
                id="catalogo-file-input" 
                type="file" 
                className="hidden" 
                onChange={(e) => handleFileSelect(e.target.files ? e.target.files[0] : null)} 
                accept=".xlsx,.xls,.csv" 
            />
          <div className="flex flex-col gap-2">
            <Button onClick={handleAutomap} disabled={!archivo || isProcessing} size="lg">
              {isProcessing ? 'Procesando...' : 'Iniciar Auto-Mapeo'}
            </Button>
            <Button variant="success" size="lg" asChild>
              <a href={route('importacion.descargarPlantilla', { tipo: 'catalogo' })}>
                <FileDown className="mr-2 h-4 w-4" /> Descargar Plantilla
              </a>
            </Button>
          </div>
        </div>

        {isProcessing && uploadProgress > 0 && <Progress value={uploadProgress} className="w-full" />}

        {errors.length > 0 && (
            <div className="space-y-2 pt-4">
            </div>
        )}

        {warnings.length > 0 && (
            <div className="space-y-2 pt-4">
                <Label className="text-orange-500 flex items-center gap-2">
                  <AlertTriangle className="h-4 w-4" /> Registro de Advertencias de Catálogo
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

        {cuentas.length > 0 && (
          <div>
            <h3 className="text-md font-medium mb-4">Previsualización y Mapeo</h3>
            <div className="border rounded-md max-h-96 overflow-y-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Su Cuenta (Desde Archivo)</TableHead>
                    <TableHead>Cuenta del Sistema Asignada</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {cuentas.map((cuenta, index) => (
                    <TableRow key={index}>
                      <TableCell className="font-medium">{cuenta.codigo_cuenta} - {cuenta.nombre_cuenta}</TableCell>
                      <TableCell>
                        <Select onValueChange={(value) => handleMapeoChange(index, value)} defaultValue={cuenta.cuenta_base_id?.toString() || 'null'}>
                          <SelectTrigger>
                            <SelectValue placeholder="-- No asignar --" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="null">-- No asignar --</SelectItem>
                            {cuentasBase.map(cb => (<SelectItem key={cb.id} value={cb.id.toString()}>{cb.nombre}</SelectItem>))}
                          </SelectContent>
                        </Select>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </CardContent>
      <CardFooter className="flex justify-between">
        <Button variant="outline" onClick={onBack}>Atrás</Button>
        <Button onClick={handleGuardarMapeo} disabled={isProcessing || cuentas.length === 0}>
          {isProcessing ? 'Guardando...' : 'Guardar Mapeo y Continuar'}
        </Button>
      </CardFooter>
    </Card>
  );
};

// --- Componente para el Paso 3 ---
const CargarEstadoFinancieroStep: React.FC<{ empresa: Empresa; onPreview: (data: any) => void; onBack: () => void; }> = ({ empresa, onPreview, onBack }) => {
  const [archivo, setArchivo] = React.useState<File | null>(null);
  const [anio, setAnio] = React.useState(new Date().getFullYear().toString());
  const [tipoEstado, setTipoEstado] = React.useState('balance_general');
  const [isProcessing, setIsProcessing] = React.useState(false);
  const [errors, setErrors] = React.useState<string[]>([]);
  const [uploadProgress, setUploadProgress] = React.useState(0);
  const [isDragging, setIsDragging] = React.useState(false);

  const handleFileChange = (file: File | null) => {
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
        handleFileChange(droppedFile);
    }
  };

  const handlePreview = async () => {
    if (!archivo) return;
    setIsProcessing(true);
    setUploadProgress(0);
    setErrors([]);
    toast.info('Validando estado financiero...');

    const formData = new FormData();
    formData.append('archivo', archivo);
    formData.append('empresa_id', empresa.id.toString());
    formData.append('anio', anio);
    formData.append('tipo_estado', tipoEstado);

    try {
      const response = await axios.post(route('importacion.previsualizarEstadoFinanciero'), formData, {
        onUploadProgress: (progressEvent) => {
          const percentCompleted = Math.round((progressEvent.loaded * 100) / (progressEvent.total || 1));
          setUploadProgress(percentCompleted);
        }
      });

      // Check for errors returned from the backend
      if (response.data.errores && response.data.errores.length > 0) {
        setErrors(response.data.errores);
        toast.error('Se encontraron errores en el archivo.', { description: 'Por favor, revise el registro de errores a continuación.' });
        // Do NOT proceed to step 4 if there are errors
        return;
      }

      // Check if there's actual data to preview
      if (response.data.datos && response.data.datos.length > 0) {
        toast.success('Validación completada. Revise la previsualización.');
        onPreview({ data: response.data.datos, anio: parseInt(anio), tipoEstado }); // Pass only the 'datos' array
      } else {
        toast.warning('El archivo se procesó, pero no se encontraron datos válidos para previsualizar.', { description: 'Puede que el archivo esté vacío o las cabeceras no sean correctas.' });
        // Do NOT proceed to step 4 if no valid data
      }
    } catch (error: any) {
      if (error.response && error.response.status === 422 && error.response.data.errors) {
        const errorMessages = Array.isArray(error.response.data.errors) ? error.response.data.errors : Object.values(error.response.data.errors).flat();
        setErrors(errorMessages as string[]);
        toast.error('Se encontraron errores en el archivo.', { description: 'Por favor, revise el registro de errores a continuación.' });
      } else {
        setErrors(['Ocurrió un error inesperado. Por favor, revise el formato del archivo.']);
        toast.error('Ocurrió un error inesperado.');
      }
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Paso 3: Cargar Estado Financiero</CardTitle>
        <CardDescription>Arrastre y suelte el estado financiero (Balance General o E. de Resultados) para el año especificado.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label htmlFor="anio">Año del Estado Financiero</Label>
            <Input id="anio" type="number" value={anio} onChange={e => setAnio(e.target.value)} placeholder="Ej. 2023" />
          </div>
          <div className="space-y-2">
            <Label htmlFor="tipo-estado">Tipo de Estado</Label>
            <Select onValueChange={setTipoEstado} value={tipoEstado}>
              <SelectTrigger id="tipo-estado">
                <SelectValue placeholder="Seleccione un tipo" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="balance_general">Balance General</SelectItem>
                <SelectItem value="estado_resultados">Estado de Resultados</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <div className="space-y-2">
            <Label 
                htmlFor="estado-file-input" 
                className={`flex flex-col items-center justify-center w-full p-6 border-2 border-dashed rounded-md cursor-pointer transition-colors 
                    ${isDragging ? 'border-primary bg-primary/10' : 'hover:bg-muted/50'}`}
                onDragEnter={handleDragEnter}
                onDragLeave={handleDragLeave}
                onDragOver={handleDragOver}
                onDrop={handleDrop}
            >
                <FileUp className={`w-10 h-10 mb-2 ${isDragging ? 'text-primary' : 'text-muted-foreground'}`} />
                <span className="font-semibold">{archivo ? archivo.name : 'Arrastre un archivo aquí o haga clic para seleccionar'}</span>
                <span className="text-xs text-muted-foreground">Excel (.xlsx, .xls, .csv)</span>
            </Label>
            <Input id="estado-file-input" type="file" className="hidden" onChange={e => handleFileChange(e.target.files ? e.target.files[0] : null)} accept=".xlsx,.xls,.csv" />
        </div>

        <div className="flex justify-end gap-2 flex-wrap">
            <Button variant="outline" asChild>
                <a href={route('importacion.descargarPlantilla')}>
                    <FileDown className="mr-2 h-4 w-4" /> Descargar Plantilla Estados Financieros
                </a>
            </Button>
        </div>

        {isProcessing && <Progress value={uploadProgress} className="w-full" />}

        {errors.length > 0 && (
            <div className="space-y-2">
                <Label className="text-destructive">Registro de Errores de Importación</Label>
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
      </CardContent>
      <CardFooter className="flex justify-between">
        <Button variant="outline" onClick={onBack}>Atrás</Button>
        <Button onClick={handlePreview} disabled={!archivo || isProcessing}>
          {isProcessing ? 'Validando...' : 'Cargar y Validar'}
        </Button>
      </CardFooter>
    </Card>
  );
};

// --- Componente para el Paso 4 ---
import ImportSuccessModal from '@/components/import/ImportSuccessModal'; // Import the new modal component

const PrevisualizarStep: React.FC<{ previewData: any; empresaId: number; onBack: () => void; }> = ({ previewData, empresaId, onBack }) => {
  const [isSaving, setIsSaving] = React.useState(false);
  const [nameFilter, setNameFilter] = React.useState('');
  const [statusFilter, setStatusFilter] = React.useState('all');
  const [currentPage, setCurrentPage] = React.useState(1);
  const itemsPerPage = 10; // You can adjust this value

  // State for the success modal
  const [showSuccessModal, setShowSuccessModal] = React.useState(false);
  const [successMessage, setSuccessMessage] = React.useState('');
  const [importedEmpresaId, setImportedEmpresaId] = React.useState<number | null>(null);


  const handleConfirm = async () => { // Make handleConfirm async
    setIsSaving(true);
    const postData = {
      empresa_id: empresaId,
      anio: previewData.anio,
      tipo_estado: previewData.tipoEstado,
      // Cambio: Solo filtrar por errores, no por cuenta_base_id (el backend crea cuentas automáticamente)
      detalles: previewData.data.filter((item: any) => item.status !== 'error'),
    };

    // --- NEW: Frontend pre-check for empty detalles ---
    if (postData.detalles.length === 0) {
      setIsSaving(false);
      toast.warning('No hay cuentas válidas para guardar.', {
        description: 'Todas las cuentas fueron filtradas debido a errores o falta de mapeo. Por favor, revise la previsualización.',
      });
      return; // Stop the function here
    }
    // --- END NEW ---

    try {
      const response = await axios.post(route('importacion.guardarEstadoFinanciero'), postData); // Use axios.post

      setIsSaving(false);

      // Set state to show the success modal
      setSuccessMessage(response.data.message || 'Estado financiero guardado con éxito.');
      setImportedEmpresaId(response.data.empresa_id || null);
      setShowSuccessModal(true);

    } catch (error: any) {
      setIsSaving(false);
      console.error('axios.post error response:', error);

      let errorMessage = 'Ocurrió un error inesperado al guardar el estado financiero.';
      if (error.response && error.response.data && error.response.data.message) {
        errorMessage = error.response.data.message;
      } else if (error.message) {
        errorMessage = error.message;
      }
      toast.error('Error al guardar el estado financiero.', { description: errorMessage });
    }
  };

  // Filtering Logic
  const filteredData = React.useMemo(() => {
    if (!Array.isArray(previewData.data)) return [];

    return previewData.data.filter((item: any) => {
      const matchesName = nameFilter === '' || 
                          item.codigo_cuenta.toLowerCase().includes(nameFilter.toLowerCase()) ||
                          item.nombre_cuenta.toLowerCase().includes(nameFilter.toLowerCase());
      
      const matchesStatus = statusFilter === 'all' || item.status === statusFilter;

      return matchesName && matchesStatus;
    });
  }, [previewData.data, nameFilter, statusFilter]);

  // Pagination Logic
  const totalPages = Math.ceil(filteredData.length / itemsPerPage);
  const paginatedData = filteredData.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

  // Reset page when filters change
  React.useEffect(() => {
    setCurrentPage(1);
  }, [nameFilter, statusFilter]);

  const isConfirmButtonDisabled = isSaving || previewData.data.some((item: any) => item.status === 'error');

  return (
    <Card>
      <CardHeader>
        <CardTitle>Paso 4: Previsualizar y Confirmar</CardTitle>
        <CardDescription>Revise los datos interpretados del estado financiero antes de guardarlos permanentemente en el sistema.</CardDescription>
      </CardHeader>
      <CardContent>
        {/* Filter Controls */}
        <div className="flex items-center gap-4 mb-4">
            <Input 
                placeholder="Filtrar por código o nombre..." 
                value={nameFilter} 
                onChange={e => setNameFilter(e.target.value)} 
                className="max-w-sm" 
            />
            <div className="flex items-center space-x-2">
                <Label htmlFor="status-filter">Estado:</Label>
                <Select value={statusFilter} onValueChange={setStatusFilter}>
                    <SelectTrigger id="status-filter" className="w-[180px]">
                        <SelectValue placeholder="Filtrar por estado" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos</SelectItem>
                        <SelectItem value="valid">Válido</SelectItem>
                        <SelectItem value="warning">Advertencia</SelectItem>
                        <SelectItem value="error">Error</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div className="border rounded-md">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Cuenta (Mapeada a Cuenta Base)</TableHead>
                <TableHead className="text-right">Valor</TableHead>
                <TableHead className="text-center">Estado</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {paginatedData.length > 0 ? (
                paginatedData.map((item: any, index: number) => {
                  let rowClass = '';
                  let statusIcon = null;
                  let statusText = '';

                  if (item.status === 'error') {
                    rowClass = 'bg-red-100/50 dark:bg-red-900/30';
                    statusIcon = <AlertCircle className="h-4 w-4 text-red-600" />;
                    statusText = 'Error';
                  } else if (item.status === 'warning') {
                    rowClass = 'bg-yellow-100/50 dark:bg-yellow-900/30';
                    statusIcon = <AlertTriangle className="h-4 w-4 text-yellow-600" />;
                    statusText = 'Advertencia';
                  } else {
                    statusText = 'Válido';
                  }

                  return (
                    <TableRow key={index} className={rowClass}>
                      <TableCell>
                        <div className="font-medium">{item.codigo_cuenta} - {item.nombre_cuenta}</div>
                        <div className="text-sm text-muted-foreground">Mapeada a: {item.cuenta_base_nombre}</div>
                        {item.row_errors && item.row_errors.length > 0 && (
                          <div className="text-xs text-red-600 mt-1">
                            {item.row_errors.map((err: string, i: number) => <p key={i}>- {err}</p>)}
                          </div>
                        )}
                        {item.row_warnings && item.row_warnings.length > 0 && (
                          <div className="text-xs text-yellow-600 mt-1">
                            {item.row_warnings.map((warn: string, i: number) => <p key={i}>- {warn}</p>)}
                          </div>
                        )}
                      </TableCell>
                      <TableCell className="text-right">{new Intl.NumberFormat('es-SV', { style: 'currency', currency: 'USD' }).format(item.saldo)}</TableCell>
                      <TableCell className="text-center">
                        <div className="flex items-center justify-center gap-1">
                          {item.status === 'error' && <Badge variant="destructive">{statusIcon} {statusText}</Badge>}
                          {item.status === 'warning' && <Badge variant="yellow">{statusIcon} {statusText}</Badge>}
                          {item.status === 'valid' && <Badge variant="green">{statusText}</Badge>}
                        </div>
                      </TableCell>
                    </TableRow>
                  );
                })
              ) : (
                <TableRow>
                  <TableCell colSpan={3} className="h-24 text-center">
                    No se encontraron resultados.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>

        {/* Pagination Controls */}
        <div className="flex items-center justify-end space-x-2 py-4">
            <Button variant="outline" size="sm" onClick={() => setCurrentPage(p => Math.max(1, p - 1))} disabled={currentPage === 1}>Anterior</Button>
            <span className="text-sm">Página {currentPage} de {totalPages}</span>
            <Button variant="outline" size="sm" onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))} disabled={currentPage === totalPages}>Siguiente</Button>
        </div>
      </CardContent>
      <CardFooter className="flex justify-between">
        <Button variant="outline" onClick={onBack}>Atrás</Button>
        <Button onClick={handleConfirm} disabled={isConfirmButtonDisabled}>
          {isSaving ? 'Guardando...' : 'Confirmar y Guardar'}
        </Button>
      </CardFooter>
      {/* Render the success modal */}
      <ImportSuccessModal
        isOpen={showSuccessModal}
        onClose={() => setShowSuccessModal(false)}
        message={successMessage}
        empresaId={importedEmpresaId}
      />
    </Card>
  );
};

// --- Componente Principal del Asistente ---
const ImportWizardPage = ({ empresas, sectores, plantillas }: { empresas: Empresa[], sectores: Sector[], plantillas: PlantillaCatalogo[] }) => {
  const [step, setStep] = React.useState(1);
  const [empresa, setEmpresa] = React.useState<Empresa | null>(null);
  const [previewData, setPreviewData] = React.useState<any>(null);

  // Dynamic Breadcrumbs
  const getBreadcrumbs = (): BreadcrumbItem[] => {
    const baseBreadcrumbs: BreadcrumbItem[] = [
      { title: 'Home', href: route('dashboard') },
      { title: 'Importación', href: route('importacion.wizard') }, // Assuming a route for the wizard itself
    ];

    if (step === 1) {
      return [...baseBreadcrumbs, { title: 'Definir Empresa', href: route('importacion.wizard') }];
    }

    if (empresa) {
      baseBreadcrumbs.push({ title: empresa.nombre, href: route('empresas.show', empresa.id) }); // Link to company show page
    }

    switch (step) {
      case 2:
        return [...baseBreadcrumbs, { title: 'Catálogo Base', href: route('importacion.wizard') }];
      case 3:
        return [...baseBreadcrumbs, { title: 'Estado Financiero', href: route('importacion.wizard') }];
      case 4:
        return [...baseBreadcrumbs, { title: 'Previsualizar', href: route('importacion.wizard') }];
      default:
        return baseBreadcrumbs;
    }
  };

  const handleEmpresaSelected = (selectedEmpresa: Empresa, action: 'goToStep2' | 'goToStep3') => {
    setEmpresa(selectedEmpresa);
    // Determine if the company has a catalog already
    const plantilla = plantillas.find(p => p.id === selectedEmpresa.plantilla_catalogo_id);
    const hasCuentasBase = plantilla && plantilla.cuentasBase && plantilla.cuentasBase.length > 0;

    if (action === 'goToStep2') { // This action is triggered when a new company is created or an existing one needs catalog setup
      if (hasCuentasBase) {
        setStep(2); // Go to MapeoCatalogoStep if catalog exists
      } else {
        setStep(2); // Go to CargarCatalogoBaseStep if no catalog
      }
    } else if (action === 'goToStep3') { // This action is triggered when an existing company has a catalog and wants to import financial statements
      setStep(3);
    }
  };

  const handleCatalogoBaseCargado = () => {
    // After base catalog is loaded, we should refresh the plantillas prop to get the new cuentasBase
    // and then proceed to the next step (CargarEstadoFinancieroStep)
    router.reload({ only: ['plantillas'], onSuccess: () => {
        setStep(3);
    }});
  };

  const handleMapeoCompleto = () => setStep(3);
  
  const handlePreview = (data: any) => {
    setPreviewData(data);
    setStep(4);
  };

  const handleBack = () => {
    if (step > 1) {
        // If we are on the preview step, and we go back, we should land on the step determined by the company's catalog status
        if (step === 4) {
            setStep(3);
            return;
        }
        // If we are on step 3, we go back to step 1 to re-evaluate the company
        if (step === 3) {
            // Check if the company has a catalog. If so, go to MapeoCatalogoStep (Step 2).
            // Otherwise, go to CargarCatalogoBaseStep (also Step 2, but different component).
            const plantilla = plantillas.find(p => p.id === empresa?.plantilla_catalogo_id);
            const hasCuentasBase = plantilla && plantilla.cuentasBase && plantilla.cuentasBase.length > 0;
            setStep(2);
            return;
        }
        // If we are on step 2 (either CargarCatalogoBaseStep or MapeoCatalogoStep), we go back to step 1
        if (step === 2) {
            setStep(1);
            return;
        }
    }
  };

  const getCuentasBaseParaEmpresa = () => {
    if (!empresa) return [];
    const plantilla = plantillas.find(p => p.id === empresa.plantilla_catalogo_id);
    return plantilla?.cuentasBase?.filter(c => c.tipo_cuenta === 'DETALLE') || [];
  };

  const renderStep = () => {
    if (step === 1) {
      return <DefineEmpresaStep empresas={empresas} sectores={sectores} onEmpresaSelected={handleEmpresaSelected} />;
    }

    if (!empresa) {
      return <p>Por favor, regrese al paso 1 y seleccione una empresa.</p>;
    }

    const plantilla = plantillas.find(p => p.id === empresa.plantilla_catalogo_id);
    const hasCuentasBase = plantilla && plantilla.cuentasBase && plantilla.cuentasBase.length > 0;

    switch (step) {
      case 2:
        if (hasCuentasBase) {
            return <MapeoCatalogoStep empresa={empresa} cuentasBase={getCuentasBaseParaEmpresa()} onMapeoCompleto={handleMapeoCompleto} onBack={handleBack} />;
        } else {
            return <CargarCatalogoBaseStep empresa={empresa} onCatalogoBaseCargado={handleCatalogoBaseCargado} onBack={handleBack} />;
        }
      case 3:
        return <CargarEstadoFinancieroStep empresa={empresa} onPreview={handlePreview} onBack={handleBack} />;
      case 4:
        return previewData && empresa ? <PrevisualizarStep previewData={previewData} empresaId={empresa.id} onBack={handleBack} /> : <p>No hay datos para previsualizar.</p>;
      default:
        return <p>Paso desconocido.</p>;
    }
  };

  const steps = [
    { id: 1, name: 'Empresa' },
    { id: 2, name: 'Catálogo Base' }, // Updated name for clarity
    { id: 3, name: 'Estado Financiero' },
    { id: 4, name: 'Confirmar' },
  ];

  return (
    <AppLayout breadcrumbs={getBreadcrumbs()}>
      <div className="p-4 sm:p-6 lg:p-8">
        <div className="max-w-4xl mx-auto">
          <div className="mb-8">
            <h1 className="text-2xl font-bold tracking-tight">Asistente de Configuración e Importación</h1>
            <p className="text-muted-foreground">Siga los pasos para configurar una empresa e importar sus datos financieros.</p>
          </div>
          
          {/* Stepper */}
          <div className="mb-8 flex items-center justify-between">
            {steps.map((s, index) => (
              <React.Fragment key={s.id}>
                <div className="flex flex-col items-center">
                  <div className={`w-8 h-8 rounded-full flex items-center justify-center ${step >= s.id ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'}`}>
                    {s.id}
                  </div>
                  <p className={`mt-2 text-sm font-medium ${step >= s.id ? 'text-primary' : 'text-muted-foreground'}`}>{s.name}</p>
                </div>
                {index < steps.length - 1 && <div className="flex-1 h-px bg-border mx-4"></div>}
              </React.Fragment>
            ))}
          </div>

          <div className="mt-8">{renderStep()}</div>
        </div>
      </div>
    </AppLayout>
  );
};

export default ImportWizardPage;