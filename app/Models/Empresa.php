<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'sector_id', 'usuario_id', 'plantilla_catalogo_id'];

    public function plantillaCatalogo(): BelongsTo
    {
        return $this->belongsTo(PlantillaCatalogo::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function catalogoCuentas(): HasMany
    {
        return $this->hasMany(CatalogoCuenta::class);
    }

    public function estadosFinancieros(): HasMany
    {
        return $this->hasMany(EstadoFinanciero::class);
    }

    public function datosVentaHistoricos(): HasMany
    {
        return $this->hasMany(DatoVentaHistorico::class);
    }

    public function ratiosCalculados(): HasMany
    {
        return $this->hasMany(RatioCalculado::class, 'empresa_id');
    }
}
