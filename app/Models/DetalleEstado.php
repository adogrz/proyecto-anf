<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleEstado extends Model
{
    use HasFactory;

    protected $table = 'detalles_estados';

    protected $fillable = ['estado_financiero_id', 'catalogo_cuenta_id', 'valor'];

    public function estadoFinanciero(): BelongsTo
    {
        return $this->belongsTo(EstadoFinanciero::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CatalogoCuenta::class, 'catalogo_cuenta_id');
    }
}
