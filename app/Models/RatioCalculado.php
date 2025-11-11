<?php

namespace App\Models;

use App\Traits\TieneRatiosFinancieros;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatioCalculado extends Model
{
    use HasFactory, TieneRatiosFinancieros;

    protected $table = 'ratios_calculados';

    protected $fillable = [
        'empresa_id',
        'anio',
        'nombre_ratio',
        'valor_ratio',
    ];

    protected $casts = [
        'valor_ratio' => 'decimal:4',
        'anio' => 'integer',
    ];

    // Relaciones
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    // Scopes útiles
    public function scopePorEmpresa($query, int $idEmpresa)
    {
        return $query->where('empresa_id', $idEmpresa);
    }

    /**
     * Compara el ratio calculado de la empresa con el valor de referencia del sector
     */
    public function compararConSector(): ?object
    {
        $ratioSector = RatioSector::porSector($this->empresa->sector_id)
            ->porRatio($this->nombre_ratio)
            ->porAnio($this->anio)
            ->first();

        if (!$ratioSector) {
            return null;
        }

        $diferencia = $this->valor_ratio - $ratioSector->valor_referencia;
        $porcentajeDiferencia = $ratioSector->valor_referencia != 0
            ? ($diferencia / $ratioSector->valor_referencia) * 100
            : 0;

        return (object) [
            'valor_empresa' => $this->valor_ratio,
            'valor_sector' => $ratioSector->valor_referencia,
            'diferencia' => $diferencia,
            'porcentaje_diferencia' => round($porcentajeDiferencia, 2),
            'mejor_que_sector' => $this->valor_ratio > $ratioSector->valor_referencia,
            'fuente_sector' => $ratioSector->fuente,
        ];
    }
}
