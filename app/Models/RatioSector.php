<?php

namespace App\Models;

use App\Traits\TieneRatiosFinancieros;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatioSector extends Model
{
    use HasFactory, TieneRatiosFinancieros;

    protected $table = 'ratios_sector';

    protected $fillable = [
        'sector_id',
        'nombre_ratio',
        'valor_referencia',
        'fuente',
    ];

    protected $casts = [
        'valor_referencia' => 'decimal:4',
    ];

    // Relaciones
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }

    // Scopes útiles
    public function scopePorSector($query, int $sectorId)
    {
        return $query->where('sector_id', $sectorId);
    }
}
