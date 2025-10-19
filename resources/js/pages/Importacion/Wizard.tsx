
import AppLayout from '@/layouts/app-layout';
import React from 'react';
import { CuentaBase, Empresa, PlantillaCatalogo, Sector } from '@/types';
import { useForm, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import axios from 'axios';

// --- Componente para el Paso 1 ---
interface DefineEmpresaStepProps {
  empresas: Empresa[];
  sectores: Sector[];
  plantillas: PlantillaCatalogo[];
  onEmpresaSelected: (empresa: Empresa) => void;
}

const DefineEmpresaStep: React.FC<DefineEmpresaStepProps> = ({ empresas, sectores, plantillas, onEmpresaSelected }) => {
  const [tipoSeleccion, setTipoSeleccion] = React.useState('existente');
  const [selectedEmpresaId, setSelectedEmpresaId] = React.useState('');

  const { data, setData, post, processing, errors } = useForm({
    nombre: '',
    sector_id: sectores[0]?.id || '',
    plantilla_catalogo_id: plantillas[0]?.id || '',
  });

  const handleSelectChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const empresaId = e.target.value;
    setSelectedEmpresaId(empresaId);
    const selected = empresas.find(emp => emp.id.toString() === empresaId);
    if (selected) {
      onEmpresaSelected(selected);
    }
  };

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('empresas.store'), {
      onSuccess: (page) => {
        const newEmpresa = (page.props as any).jetstream.flash.empresa as Empresa;
        if (newEmpresa) {
          onEmpresaSelected(newEmpresa);
        }
      },
    });
  };

  return (
    <div>
      <h2 className="text-lg font-medium text-gray-900 dark:text-white">Paso 1: Definir la Empresa</h2>
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Seleccione una empresa existente o cree una nueva.</p>
      <fieldset className="mt-4">
        <div className="flex items-center space-x-4">
          <div className="flex items-center">
            <input id="existente" name="tipo-seleccion" type="radio" checked={tipoSeleccion === 'existente'} onChange={() => setTipoSeleccion('existente')} className="h-4 w-4 text-indigo-600 border-gray-300" />
            <label htmlFor="existente" className="ml-3 block text-sm font-medium">Empresa Existente</label>
          </div>
          <div className="flex items-center">
            <input id="nueva" name="tipo-seleccion" type="radio" checked={tipoSeleccion === 'nueva'} onChange={() => setTipoSeleccion('nueva')} className="h-4 w-4 text-indigo-600 border-gray-300" />
            <label htmlFor="nueva" className="ml-3 block text-sm font-medium">Nueva Empresa</label>
          </div>
        </div>
      </fieldset>
      {tipoSeleccion === 'existente' ? (
        <div className="mt-4">
          <select value={selectedEmpresaId} onChange={handleSelectChange} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700">
            <option value="">-- Seleccione una empresa --</option>
            {empresas.map((empresa) => (<option key={empresa.id} value={empresa.id}>{empresa.nombre}</option>))}
          </select>
        </div>
      ) : (
        <form className="mt-4 space-y-4" onSubmit={handleCreateSubmit}>
          <input type="text" value={data.nombre} onChange={e => setData('nombre', e.target.value)} placeholder="Nombre de la Empresa" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
          {errors.nombre && <p className="text-sm text-red-600">{errors.nombre}</p>}
          <select value={data.sector_id} onChange={e => setData('sector_id', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700">
            {sectores.map((sector) => (<option key={sector.id} value={sector.id}>{sector.nombre}</option>))}
          </select>
          <select value={data.plantilla_catalogo_id} onChange={e => setData('plantilla_catalogo_id', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700">
            {plantillas.map((plantilla) => (<option key={plantilla.id} value={plantilla.id}>{plantilla.nombre}</option>))}
          </select>
          <Button type="submit" disabled={processing}>Crear y Continuar</Button>
        </form>
      )}
    </div>
  );
};

// --- Componente para el Paso 2 ---
interface MapeoCatalogoStepProps {
  empresa: Empresa;
  cuentasBase: CuentaBase[];
  onMapeoCompleto: () => void;
}

const MapeoCatalogoStep: React.FC<MapeoCatalogoStepProps> = ({ empresa, cuentasBase, onMapeoCompleto }) => {
  const [archivo, setArchivo] = React.useState<File | null>(null);
  const [cuentas, setCuentas] = React.useState<any[]>([]);
  const [isProcessing, setIsProcessing] = React.useState(false);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => e.target.files && setArchivo(e.target.files[0]);

  const handleAutomap = async () => {
    if (!archivo) return;
    setIsProcessing(true);
    const formData = new FormData();
    formData.append('archivo', archivo);
    formData.append('plantilla_catalogo_id', empresa.plantilla_catalogo_id.toString());
    try {
      const response = await axios.post(route('importacion.automap'), formData);
      setCuentas(response.data);
    } catch (error) { console.error(error); }
    finally { setIsProcessing(false); }
  };

  const handleMapeoChange = (index: number, cuentaBaseId: string) => {
    const nuevasCuentas = [...cuentas];
    nuevasCuentas[index].cuenta_base_id = cuentaBaseId ? parseInt(cuentaBaseId) : null;
    setCuentas(nuevasCuentas);
  };

  const handleGuardarMapeo = async () => {
    setIsProcessing(true);
    try {
      await axios.post(route('importacion.guardarMapeo'), {
        empresa_id: empresa.id,
        cuentas: cuentas,
      });
      onMapeoCompleto();
    } catch (error) { console.error(error); }
    finally { setIsProcessing(false); }
  };

  return (
    <div>
      <h2 className="text-lg font-medium">Paso 2: Cargar y Mapear Catálogo</h2>
      <p className="mt-1 text-sm text-gray-600">Suba su catálogo para que el sistema intente mapearlo.</p>
      <div className="mt-4 flex items-center gap-4">
        <input type="file" onChange={handleFileChange} className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100"/>
        <Button onClick={handleAutomap} disabled={!archivo || isProcessing}>{isProcessing ? 'Procesando...' : 'Iniciar Auto-Mapeo'}</Button>
      </div>
      {cuentas.length > 0 && (
        <div className="mt-6">
          <table className="min-w-full divide-y divide-gray-300">
            <thead><tr><th className="py-3.5 pl-4 pr-3 text-left text-sm font-semibold">Su Cuenta</th><th className="px-3 py-3.5 text-left text-sm font-semibold">Cuenta del Sistema Asignada</th></tr></thead>
            <tbody className="divide-y divide-gray-200">
              {cuentas.map((cuenta, index) => (
                <tr key={index}>
                  <td className="py-4 pl-4 pr-3 text-sm font-medium">{cuenta.codigo_cuenta} - {cuenta.nombre_cuenta}</td>
                  <td className="px-3 py-4 text-sm">
                    <select value={cuenta.cuenta_base_id || ''} onChange={(e) => handleMapeoChange(index, e.target.value)} className="block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-700">
                      <option value="">-- No asignar --</option>
                      {cuentasBase.map(cb => (<option key={cb.id} value={cb.id}>{cb.nombre}</option>))}
                    </select>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <div className="mt-6 flex justify-end">
            <Button onClick={handleGuardarMapeo} disabled={isProcessing}>Guardar Mapeo y Continuar</Button>
          </div>
        </div>
      )}
    </div>
  );
};

// --- Componente para el Paso 3 ---
const CargarEstadoFinancieroStep: React.FC<{ empresa: Empresa; onPreview: (data: any) => void; }> = ({ empresa, onPreview }) => {
  const [archivo, setArchivo] = React.useState<File | null>(null);
  const [anio, setAnio] = React.useState(new Date().getFullYear());
  const [tipoEstado, setTipoEstado] = React.useState('balance_general');
  const [isProcessing, setIsProcessing] = React.useState(false);
  const [errors, setErrors] = React.useState<string[]>([]);

  const handlePreview = async () => {
    if (!archivo) return;
    setIsProcessing(true);
    setErrors([]);
    const formData = new FormData();
    formData.append('archivo', archivo);
    formData.append('empresa_id', empresa.id.toString());
    formData.append('anio', anio.toString());
    formData.append('tipo_estado', tipoEstado);

    try {
      const response = await axios.post(route('importacion.previsualizar'), formData);
      onPreview({ data: response.data, anio, tipoEstado });
    } catch (error: any) {
      if (error.response && error.response.status === 422) {
        setErrors(error.response.data.errors);
      } else {
        setErrors(['Ocurrió un error inesperado.']);
      }
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <div>
      <h2 className="text-lg font-medium">Paso 3: Cargar Estado Financiero</h2>
      <div className="mt-4 space-y-4">
        <input type="number" value={anio} onChange={e => setAnio(parseInt(e.target.value))} placeholder="Año" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
        <select value={tipoEstado} onChange={e => setTipoEstado(e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
          <option value="balance_general">Balance General</option>
          <option value="estado_resultados">Estado de Resultados</option>
        </select>
        <input type="file" onChange={e => e.target.files && setArchivo(e.target.files[0])} className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0"/>
      </div>
      {errors.length > 0 && (
        <div className="mt-4 rounded-md bg-red-50 p-4">
          <h3 className="text-sm font-medium text-red-800">Errores de Validación:</h3>
          <ul className="list-disc pl-5 mt-2 text-sm text-red-700 space-y-1">
            {errors.map((error, i) => <li key={i}>{error}</li>)}
          </ul>
        </div>
      )}
      <div className="mt-6 flex justify-end">
        <Button onClick={handlePreview} disabled={!archivo || isProcessing}>{isProcessing ? 'Procesando...' : 'Cargar y Previsualizar'}</Button>
      </div>
    </div>
  );
};

// --- Componente para el Paso 4 ---
const PrevisualizarStep: React.FC<{ previewData: any; empresaId: number; }> = ({ previewData, empresaId }) => {
  const [isSaving, setIsSaving] = React.useState(false);

  const handleConfirm = () => {
    setIsSaving(true);
    const postData = {
      empresa_id: empresaId,
      anio: previewData.anio,
      tipo_estado: previewData.tipoEstado,
      detalles: previewData.data,
    };

    router.post(route('importacion.guardarEstadoFinanciero'), postData, {
      onFinish: () => setIsSaving(false),
    });
  };

  return (
    <div>
      <h2 className="text-lg font-medium">Paso 4: Previsualizar y Confirmar</h2>
      <p className="mt-1 text-sm text-gray-600">Revise los datos interpretados antes de guardarlos.</p>
      <div className="mt-6 flow-root">
        <table className="min-w-full divide-y divide-gray-300">
          <thead><tr><th className="py-3.5 pl-4 pr-3 text-left text-sm font-semibold">Cuenta</th><th className="px-3 py-3.5 text-left text-sm font-semibold">Valor</th></tr></thead>
          <tbody className="divide-y divide-gray-200">
            {previewData.data.map((item: any, index: number) => (
              <tr key={index}>
                <td className="py-4 pl-4 pr-3 text-sm font-medium">{item.nombre_cuenta} ({item.cuenta_base_nombre})</td>
                <td className="px-3 py-4 text-sm">{new Intl.NumberFormat('es-SV', { style: 'currency', currency: 'USD' }).format(item.valor)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="mt-6 flex justify-end">
        <Button onClick={handleConfirm} disabled={isSaving}>{isSaving ? 'Guardando...' : 'Confirmar y Guardar'}</Button>
      </div>
    </div>
  );
};


// --- Componente Principal del Asistente ---
const ImportWizardPage = ({ empresas, sectores, plantillas }: { empresas: Empresa[], sectores: Sector[], plantillas: PlantillaCatalogo[] }) => {
  const [step, setStep] = React.useState(1);
  const [empresa, setEmpresa] = React.useState<Empresa | null>(null);
  const [previewData, setPreviewData] = React.useState<any>(null);

  const handleEmpresaSelected = (selectedEmpresa: Empresa) => {
    setEmpresa(selectedEmpresa);
    setStep(2);
  };

  const handleMapeoCompleto = () => setStep(3);
  
  const handlePreview = (data: any) => {
    setPreviewData(data);
    setStep(4);
  };

  const getCuentasBaseParaEmpresa = () => {
    if (!empresa) return [];
    const plantilla = plantillas.find(p => p.id === empresa.plantilla_catalogo_id);
    return plantilla?.cuentasBase?.filter(c => c.tipo_cuenta === 'DETALLE') || [];
  };

  const renderStep = () => {
    switch (step) {
      case 1:
        return <DefineEmpresaStep empresas={empresas} sectores={sectores} plantillas={plantillas} onEmpresaSelected={handleEmpresaSelected} />;
      case 2:
        return empresa ? <MapeoCatalogoStep empresa={empresa} cuentasBase={getCuentasBaseParaEmpresa()} onMapeoCompleto={handleMapeoCompleto} /> : <p>Por favor, complete el paso 1.</p>;
      case 3:
        return empresa ? <CargarEstadoFinancieroStep empresa={empresa} onPreview={handlePreview} /> : <p>Por favor, complete los pasos anteriores.</p>;
      case 4:
        return previewData && empresa ? <PrevisualizarStep previewData={previewData} empresaId={empresa.id} /> : <p>No hay datos para previsualizar.</p>;
      default:
        return <p>Paso desconocido.</p>;
    }
  };

  return (
    <AppLayout>
      <div className="p-4 sm:p-6 lg:p-8">
        <div className="max-w-4xl mx-auto">
          <h1 className="text-2xl font-semibold">Asistente de Configuración e Importación</h1>
          <div className="mt-8"><div className="p-6  rounded-lg shadow-md">{renderStep()}</div></div>
        </div>
      </div>
    </AppLayout>
  );
};

export default ImportWizardPage;
