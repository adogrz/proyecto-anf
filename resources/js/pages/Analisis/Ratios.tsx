import React from "react";
import { Head, useForm } from "@inertiajs/react";
import AppLayout from "@/layouts/app-layout";

// ---- Tipos ----
type YearData = {
  AC?: number; PC?: number; Inv?: number; AT?: number; PT?: number; PAT?: number;
  VN?: number; COGS?: number; UN?: number;
  label?: string;
};
type Labels = { y1: string; y2: string };
type ComparacionItem = { v1: number | null; v2: number | null; type: "num" | "pct"; name: string; };
type ResultadosPayload = {
  labels: Labels;
  y1: Record<string, number>;
  y2: Record<string, number>;
  comparacion: Record<string, ComparacionItem>;
};
type PageProps = {
  resultados?: ResultadosPayload | null;
  oldData?: { y1?: YearData; y2?: YearData; labels?: Partial<Labels> } | null;
  errors?: Record<string, string>;
};

export default function Ratios({ resultados = null, oldData = null }: PageProps) {
  const { data, setData, post, processing } = useForm<{
    y1: YearData; y2: YearData; labels: Labels;
  }>({
    y1: oldData?.y1 ?? {},
    y2: oldData?.y2 ?? {},
    labels: { y1: oldData?.labels?.y1 ?? "Año 1", y2: oldData?.labels?.y2 ?? "Año 2" },
  });

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post("/calculo-ratios"); // sin Ziggy
  };

  const num = (v: any) => Number(v ?? 0);
  const fmtNum = (v: number) => Number(v ?? 0).toLocaleString("es-SV", { maximumFractionDigits: 2 });
  const fmtPct = (v: number) => `${(Number(v ?? 0) * 100).toFixed(2)}%`;
  const pctChange = (a: number, b: number) => (a ? (b - a) / a : b ? 1 : 0);

  const RatioRow: React.FC<{ r: ComparacionItem }> = ({ r }) => {
    const v1 = num(r.v1), v2 = num(r.v2);
    const delta = v2 - v1, dperc = pctChange(v1, v2);
    const up = v2 >= v1, isPct = r.type === "pct";
    return (
      <tr className="text-gray-200">
        <td className="px-6 py-4">{r.name}</td>
        <td className="px-6 py-4 text-right font-semibold">{isPct ? fmtPct(v1) : fmtNum(v1)}</td>
        <td className="px-6 py-4 text-right font-semibold">{isPct ? fmtPct(v2) : fmtNum(v2)}</td>
        <td className="px-6 py-4 text-right">{fmtNum(delta)}</td>
        <td className="px-6 py-4 text-right">{fmtPct(dperc)}</td>
        <td className="px-6 py-4">
          <span className={`px-2 py-1 rounded text-xs ${up ? "bg-emerald-500/20 text-emerald-300" : "bg-rose-500/20 text-rose-300"}`}>
            {up ? "↑ Mejora" : "↓ Empeora"}
          </span>
        </td>
      </tr>
    );
  };

  const CAMPOS: Array<{ k: keyof YearData; label: string }> = [
    { k: "AC", label: "Activos Corrientes (AC)" },
    { k: "PC", label: "Pasivos Corrientes (PC)" },
    { k: "Inv", label: "Inventario (Inv)" },
    { k: "AT", label: "Activos Totales (AT)" },
    { k: "PT", label: "Pasivos Totales (PT)" },
    { k: "PAT", label: "Patrimonio (PAT)" },
    { k: "VN", label: "Ventas Netas (VN)" },
    { k: "COGS", label: "Costo de Ventas (COGS)" },
    { k: "UN", label: "Utilidad Neta (UN)" },
  ];

  return (
    <AppLayout>
      <Head title="Análisis de Ratios" />
      <div className="max-w-7xl mx-auto p-6">
        <h1 className="text-2xl font-semibold">Ratios Comparativos (2 años)</h1>

        {/* Formulario */}
        <form onSubmit={onSubmit} className="mt-6">
          {/* Etiquetas de años */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-white/5 border border-white/10 rounded-xl p-4">
              <label className="text-sm text-gray-300">Etiqueta Año 1</label>
              <input
                className="w-full mt-1 bg-white/10 border border-white/10 rounded px-2 py-1"
                value={data.labels.y1}
                onChange={(e) => setData("labels", { ...data.labels, y1: e.target.value })}
              />
            </div>
            <div className="bg-white/5 border border-white/10 rounded-xl p-4">
              <label className="text-sm text-gray-300">Etiqueta Año 2</label>
              <input
                className="w-full mt-1 bg-white/10 border border-white/10 rounded px-2 py-1"
                value={data.labels.y2}
                onChange={(e) => setData("labels", { ...data.labels, y2: e.target.value })}
              />
            </div>
            <div className="bg-white/5 border border-white/10 rounded-xl p-4 flex items-end">
              <button disabled={processing} className="w-full py-2 rounded-lg bg-indigo-500 hover:bg-indigo-600 disabled:opacity-50">
                Calcular
              </button>
            </div>
          </div>

          {/* Campos por año */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            {CAMPOS.map((f) => (
              <div key={String(f.k)} className="bg-white/5 border border-white/10 rounded-xl p-4">
                <div className="text-xs text-gray-400">{f.label}</div>
                <input
                  type="number" step="0.01" placeholder={`${data.labels.y1}`}
                  className="w-full mt-1 bg-white/10 border border-white/10 rounded px-2 py-1"
                  value={data.y1[f.k] ?? ""}
                  onChange={(e) => setData("y1", { ...data.y1, [f.k]: Number(e.target.value) })}
                />
                <input
                  type="number" step="0.01" placeholder={`${data.labels.y2}`}
                  className="w-full mt-2 bg-white/10 border border-white/10 rounded px-2 py-1"
                  value={data.y2[f.k] ?? ""}
                  onChange={(e) => setData("y2", { ...data.y2, [f.k]: Number(e.target.value) })}
                />
              </div>
            ))}
          </div>
        </form>

        {/* Resultados */}
        {resultados && (
          <div className="mt-8 bg-white/5 border border-white/10 rounded-xl overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead className="text-gray-400">
                <tr>
                  <th className="px-6 py-3 text-left">Ratio</th>
                  <th className="px-6 py-3 text-right">{resultados.labels?.y1 ?? "Año 1"}</th>
                  <th className="px-6 py-3 text-right">{resultados.labels?.y2 ?? "Año 2"}</th>
                  <th className="px-6 py-3 text-right">Δ Abs</th>
                  <th className="px-6 py-3 text-right">Δ %</th>
                  <th className="px-6 py-3 text-left">Estado</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/10">
                {Object.entries(resultados.comparacion).map(([key, r]) => (
                  <RatioRow key={key} r={r as ComparacionItem} />
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
