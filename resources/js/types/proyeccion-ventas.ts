/**
 * Tipo que representa un dato de venta histórico.
 */
export interface DatoVentaHistorico {
    id: number;
    empresa_id: number;
    anio: number;
    mes: number;
    monto: number;
    created_at?: string;
    updated_at?: string;
}
