<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoFinanciero extends Model
{
    use HasFactory;

    protected $table = 'estados_financieros';

    protected $fillable = ['empresa_id', 'anio', 'tipo_estado'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleEstado::class);
    }
}
