<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EjecucionProyeccion extends Model
{
    protected $table = 'ejecucion_proyecciones';

    protected $fillable = [
        'empresa_id',
        'descripcion',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function proyeccionesVentas(): HasMany
    {
        return $this->hasMany(ProyeccionVenta::class, 'ejecucion_id');
    }

    public function scopeByEmpresa($query, $empresaId): Builder
    {
        return $query->where('empresa_id', $empresaId);
    }
}
