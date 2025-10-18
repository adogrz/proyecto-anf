<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogoCuenta extends Model
{
    use HasFactory;

    protected $table = 'catalogos_cuentas';

    protected $fillable = ['empresa_id', 'codigo_cuenta', 'nombre_cuenta', 'cuenta_base_id'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cuentaBase(): BelongsTo
    {
        return $this->belongsTo(CuentaBase::class);
    }
}
