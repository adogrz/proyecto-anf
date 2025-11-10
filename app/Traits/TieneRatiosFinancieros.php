<?php

namespace App\Traits;

trait TieneRatiosFinancieros
{
    // Constantes para nombres de ratios
    public const RAZON_CIRCULANTE = 'razon_circulante';
    public const PRUEBA_ACIDA = 'prueba_acida';
    public const ROTACION_INVENTARIO = 'rotacion_inventario';
    public const DIAS_INVENTARIO = 'dias_inventario';
    public const ROTACION_ACTIVOS = 'rotacion_activos';
    public const GRADO_ENDEUDAMIENTO = 'grado_endeudamiento';
    public const ENDEUDAMIENTO_PATRIMONIAL = 'endeudamiento_patrimonial';
    public const ROE = 'roe';
    public const ROA = 'roa';

    private static array $nombresRatios = [
        self::RAZON_CIRCULANTE => 'Razón Circulante',
        self::PRUEBA_ACIDA => 'Prueba Ácida',
        self::ROTACION_INVENTARIO => 'Rotación de Inventario',
        self::DIAS_INVENTARIO => 'Días de Inventario',
        self::ROTACION_ACTIVOS => 'Rotación de Activos Totales',
        self::GRADO_ENDEUDAMIENTO => 'Grado de Endeudamiento',
        self::ENDEUDAMIENTO_PATRIMONIAL => 'Endeudamiento Patrimonial',
        self::ROE => 'ROE (Rentabilidad sobre Patrimonio)',
        self::ROA => 'ROA (Rentabilidad sobre Activos)',
    ];

    private static array $formulasRatios = [
        self::RAZON_CIRCULANTE => 'Activo Circulante / Pasivo Circulante',
        self::PRUEBA_ACIDA => '(Activo Circulante - Inventario) / Pasivo Circulante',
        self::ROTACION_INVENTARIO => 'Costo de Ventas / Inventario',
        self::DIAS_INVENTARIO => '365 / (Costo de Ventas / Inventario)',
        self::ROTACION_ACTIVOS => 'Ventas / Activo Total',
        self::GRADO_ENDEUDAMIENTO => 'Pasivo Total / Activo Total',
        self::ENDEUDAMIENTO_PATRIMONIAL => 'Pasivo Total / Patrimonio',
        self::ROE => 'Utilidad Neta / Patrimonio',
        self::ROA => 'Utilidad Neta / Activos Totales',
    ];

    /**
     * Accessor para obtener el nombre amigable del ratio
     */
    public function getNombreAmigableAttribute(): string
    {
        return self::$nombresRatios[$this->nombre_ratio] ?? $this->nombre_ratio;
    }

    /**
     * Accessor para obtener la fórmula del ratio
     */
    public function getFormulaAttribute(): string
    {
        return self::$formulasRatios[$this->nombre_ratio] ?? 'N/A';
    }

    /**
     * Obtener todos los nombres de ratios disponibles
     */
    public static function obtenerNombresRatios(): array
    {
        return self::$nombresRatios;
    }

    /**
     * Obtener todas las fórmulas de ratios disponibles
     */
    public static function obtenerFormulas(): array
    {
        return self::$formulasRatios;
    }

    /**
     * Verificar si un nombre de ratio es válido
     */
    public static function esRatioValido(string $nombreRatio): bool
    {
        return array_key_exists($nombreRatio, self::$nombresRatios);
    }

    /**
     * Scope para ordenar por último año
     */
    public function scopeUltimoAnio($query)
    {
        return $query->orderBy('anio', 'desc');
    }

    /**
     * Scope para filtrar por año
     */
    public function scopePorAnio($query, int $anio)
    {
        return $query->where('anio', $anio);
    }

    /**
     * Scope para filtrar por nombre de ratio
     */
    public function scopePorRatio($query, string $nombreRatio)
    {
        return $query->where('nombre_ratio', $nombreRatio);
    }
}
