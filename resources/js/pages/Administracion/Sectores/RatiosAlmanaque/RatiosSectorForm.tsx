import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { router } from "@inertiajs/react";
import AppLayout from '@/layouts/app-layout';
import { z } from "zod";
const ratiosFijos = [
  { nombre_ratio: "Razon circulante", valor_referencia: "", fuente: "" },
  { nombre_ratio: "Prueba acida", valor_referencia: "", fuente: "" },
  { nombre_ratio: "Capital de trabajo", valor_referencia: "", fuente: "" },
  { nombre_ratio: "Rotación de inventario", valor_referencia: "", fuente: "" },
  { nombre_ratio: "Dias de inventario", valor_referencia: "", fuente: "" },
  { nombre_ratio: "Rotacion de activos totales", valor_referencia: "", fuente: "" },
  { nombre_ratio: "Grado de endeudamiento", valor_referencia: "", fuente: "" },
  { nombre_ratio: "Endeudamiento patrimonial", valor_referencia: "", fuente: "" },
  { nombre_ratio: "Rentabilidad neta del patrimonio (ROE)", valor_referencia: "", fuente: "" },
  { nombre_ratio: "Rentabilidad  del activo (ROA)", valor_referencia: "", fuente: "" },
]

const RatioSchema = z.object({
  valor_referencia: z.string()
    .min(1, { message: "El valor de referencia es obligatorio." })
    .transform(val => parseFloat(val)) // Convierte el string a número decimal
    .refine(val => !isNaN(val) && val >= 0, { // Refina para asegurar que sea un número válido y no negativo
      message: "Debe ser un número válido mayor o igual a cero.",
    }),

  // fuente es un string
  fuente: z.string().max(255).nullable().optional(),
});

const RatiosArraySchema = z.array(RatioSchema);

interface RatioError {
  valor_referencia?: string;
  fuente?: string;
}
type RatiosErrors = RatioError[];

export default function RatiosForm({ sector, ratiosIniciales = [] }) {

  const obtenerRatiosFijos = () => {
    return ratiosFijos.map(ratioFijo => {
      const ratioExistente = ratiosIniciales.find(r => r.nombre_ratio === ratioFijo.nombre_ratio);
      if (ratioExistente) {
        return {
          ...ratioFijo,
          id: ratioExistente.id,
          valor_referencia: ratioExistente.valor_referencia,
          fuente: ratioExistente.fuente,
        };
      }

      return {
        ...ratioFijo,
        id: null,
        valor_referencia: "0.00",
        fuente: "",
      };
    });
  }
  const [ratios, setRatios] = useState(obtenerRatiosFijos);
  const [validationErrors, setValidationErrors] = useState<RatiosErrors>([]);

  const actualizarCampo = (index, campo, valor) => {
    const nuevos = [...ratios];
    nuevos[index][campo] = valor;
    setRatios(nuevos);
  };

  const guardar = () => {
    setValidationErrors([]);
    const result = RatiosArraySchema.safeParse(ratios);
    if (!result.success) {
      const newErrors: RatiosErrors = ratios.map(() => ({}));
      result.error.issues.forEach(issue => {
        const index = issue.path[0] as number;
        const field = issue.path[1] as keyof RatioError;

        newErrors[index][field] = issue.message;
      });
      setValidationErrors(newErrors);
      return;
    }
    router.post(`/administracion/sectores/${sector.id}/ratios/guardar`, {
      ratios,
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
                    <p className="text-sm">{ratio.nombre_ratio}</p>
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
