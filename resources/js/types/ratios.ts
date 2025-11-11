// Tipos compartidos para el módulo de Análisis de Ratios

export interface Empresa {
    id: number;
    nombre: string;
    sector: string;
    sector_id: number;
}

export interface MetricasResumen {
    total_ratios: number;
    mejor_categoria: {
        nombre: string;
        porcentaje: number;
    } | null;
    categoria_oportunidad: {
        nombre: string;
        porcentaje: number;
    } | null;
    mejor_mejora: {
        nombre: string;
        variacion: number;
    } | null;
}

export interface PreviewBenchmark {
    ratios_cumplen: number;
    total_ratios: number;
    porcentaje: number;
}

export interface PreviewPromedio {
    superiores: number;
    total_categorias: number;
}

export interface PreviewEvolucion {
    anios_datos: number;
    tendencia_general: 'ascendente' | 'descendente' | 'estable' | 'sin_datos';
}

export interface Benchmark {
    valor: number;
    fuente: string | null;
    diferencia: {
        absoluta: number;
        porcentual: number;
    };
    cumple: boolean;
    estado: string;
    interpretacion: string;
}

export interface PromedioSector {
    valor: number;
    cantidad_empresas: number;
    minimo: number;
    maximo: number;
    diferencia: {
        absoluta: number;
        porcentual: number;
    };
    posicion_relativa: string;
    interpretacion: string;
}

export interface Comparacion {
    nombre_ratio: string;
    clave_ratio: string;
    valor_empresa: number;
    formula: string;
    categoria: string;
    benchmark?: Benchmark | null;
    promedio_sector?: PromedioSector | null;
}

export interface RatioData {
    nombre_ratio: string;
    clave_ratio: string;
    formula: string;
    categoria: string;
    serie_empresa: Array<{ anio: number; valor: number }>;
    benchmark_sector: { valor: number; fuente: string } | null;
    promedios_sector: Array<{ anio: number; valor: number }>;
    tendencia: {
        direccion: string;
        variacion: number;
        valor_inicial: number;
        valor_final: number;
    };
}
