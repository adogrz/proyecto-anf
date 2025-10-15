<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProyeccionVenta extends Model
{
    use HasFactory;

    protected $table = 'proyecciones_ventas';

    protected $fillable = ['empresa_id', 'anio', 'mes', 'monto_ventas', 'tipo'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
