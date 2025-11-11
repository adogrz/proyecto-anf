import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { router } from "@inertiajs/react";
import AppLayout from '@/layouts/app-layout';
import { z } from "zod";

// Asegúrate de incluir nombre_ratio en el esquema de Zod
const RatioSchema = z.object({
  id: z.number().nullable().optional(), // 👈 LÍNEA AÑADIDA
  nombre_ratio: z.string(), // Necesario para la validación y el envío
  valor_referencia: z.string()
    .min(1, { message: "El valor de referencia es obligatorio." })
    .transform(val => parseFloat(val))
    .refine(val => !isNaN(val) && val >= 0, {
      message: "Debe ser un número válido mayor o igual a cero.",
    }),
  fuente: z.string().max(255).nullable().optional(),
});

const RatiosArraySchema = z.array(RatioSchema);

interface RatioError {
  valor_referencia?: string;
  fuente?: string;
}
type RatiosErrors = RatioError[];

// Renombramos nombresFijos a nombresRatiosMapa para mayor claridad
export default function RatiosForm({ sector, ratiosIniciales = [], nombresFijos = {} }) {

  // 🚨 CORRECCIÓN CLAVE: Inicializar la lista base a partir del objeto (mapa)
  // Usamos Object.entries para obtener [clave, valor] y luego .map para transformar.
  const generarRatiosBase = () => {
    return Object.entries(nombresFijos).map(([nombre_ratio_clave, nombre_amigable]) => ({
      // nombre_ratio es la clave técnica que se guarda en la DB
      nombre_ratio: nombre_ratio_clave, 
      // nombre_amigable es para mostrar en la UI
      nombre_amigable: nombre_amigable, 
      valor_referencia: "",
      fuente: "",
      id: null,
    }));
  };

  const obtenerRatiosIniciales = () => {
    // 1. Obtener la lista base de todos los ratios
    const ratiosBase = generarRatiosBase();
    
    // 2. Mapear los datos iniciales
    return ratiosBase.map(ratioBase => {
      const ratioExistente = ratiosIniciales.find(r => r.nombre_ratio === ratioBase.nombre_ratio);
      
      if (ratioExistente) {
        return {
          ...ratioBase,
          id: ratioExistente.id,
          // Convertimos a string para que el input type="number" lo maneje correctamente
          valor_referencia: String(ratioExistente.valor_referencia), 
          fuente: ratioExistente.fuente,
        };
      }

      // Si no existe, devolvemos la base. Usamos "" en lugar de "0.00" 
      // para evitar que Zod marque un error al inicio si el campo es obligatorio.
      return { 
        ...ratioBase, 
        valor_referencia: "",
      }; 
    });
  }

  // Llamamos a la función de inicialización
  const [ratios, setRatios] = useState(obtenerRatiosIniciales);
  const [validationErrors, setValidationErrors] = useState<RatiosErrors>([]);

  const actualizarCampo = (index, campo, valor) => {
    const nuevos = [...ratios];
    nuevos[index][campo] = valor;
    setRatios(nuevos);
  };

  const guardar = () => {
    setValidationErrors([]);
    
    // 🚨 IMPORTANTE: Asegúrate de que el estado `ratios` sea parseable por Zod
    const result = RatiosArraySchema.safeParse(ratios);
    
    if (!result.success) {
      // ... (Manejo de errores sin cambios)
      const newErrors: RatiosErrors = ratios.map(() => ({}));
      result.error.issues.forEach(issue => {
        const index = issue.path[0] as number;
        const field = issue.path[1] as keyof RatioError;

        if (field === 'valor_referencia' || field === 'fuente') {
            newErrors[index][field] = issue.message;
        }
      });
      setValidationErrors(newErrors);
      return;
    }
    
    // 💡 Opcional: limpiar los datos a enviar si incluyes 'nombre_amigable'
    const ratiosToSend = result.data.map(ratio => ({
        id: ratio.id,
        nombre_ratio: ratio.nombre_ratio,
        valor_referencia: ratio.valor_referencia,
        fuente: ratio.fuente,
    }));

    router.post(`/administracion/sectores/${sector.id}/ratios/guardar`, {
      ratios: ratiosToSend,
    }, {
      onSuccess: () => {
        window.history.back();
      }
    });
  };

  return (
    <AppLayout>
      <div className="p-6">
        <h2 className="text-lg font-semibold mb-4">
          Ratios financieros de {sector.nombre}
        </h2>

        <div className="overflow-x-auto border rounded-md">
          <table className="min-w-full text-sm">
            <thead className="bg-gray-600 ">
              <tr>
                <th className="p-2 text-left w-auto">Nombre del Ratio</th>
                <th className="p-2 text-left">Valor Referencia</th>
                <th className="p-2 text-left">Fuente</th>
              </tr>
            </thead>
            <tbody>
              {ratios.map((ratio, i) => (
                <tr key={i} className="border-t">
                  <td className="p-2">
                    {/* 🚨 CLAVE: Mostrar nombre_amigable. Si no lo tienes, usa nombre_ratio */}
                    <p className="text-sm">{ratio.nombre_amigable || ratio.nombre_ratio}</p>
                  </td>
                  <td className="p-2">
                    <Input
                      value={ratio.valor_referencia}
                      onChange={(e) => actualizarCampo(i, "valor_referencia", e.target.value)}
                      type="number"
                      step="0.01"
                      placeholder="Ej. 45.3"
                      min="0.00"
                      className={validationErrors[i]?.valor_referencia ? "border-red-500" : ""}
                    />
                    {validationErrors[i]?.valor_referencia && (<p className="text-xs text-red-500 mt-1">
                      {validationErrors[i].valor_referencia}
                    </p>)}
                  </td>
                  <td className="p-2">
                    <Input
                      value={ratio.fuente}
                      onChange={(e) => actualizarCampo(i, "fuente", e.target.value)}
                      placeholder="Fuente o publicación"
                      className={validationErrors[i]?.fuente ? "border-red-500" : ""}
                    />
                    {validationErrors[i]?.fuente && (<p className="text-xs text-red-500 mt-1">
                      {validationErrors[i].fuente}
                    </p>)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="flex gap-2 mt-4">
          <Button onClick={guardar}>💾 Guardar</Button>
        </div>
      </div>
    </AppLayout>
  );

}