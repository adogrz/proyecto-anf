<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatoVentaHistorico extends Model
{
    protected $table = 'datos_venta_historicos';

    protected $fillable = [
        'empresa_id',
        'anio',
        'mes',
        'monto',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function scopeByEmpresa($query, $empresaId): Builder
    {
        return $query->where('empresa_id', $empresaId);
    }
}
