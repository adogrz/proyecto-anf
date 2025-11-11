<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaBase extends Model
{
    use HasFactory;

    protected $table = 'cuentas_base';

    protected $fillable = [
        'plantilla_catalogo_id',
        'parent_id',
        'codigo',
        'nombre',
        'tipo_cuenta',
        'naturaleza',
    ];

    public function plantillaCatalogo(): BelongsTo
    {
        return $this->belongsTo(PlantillaCatalogo::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CuentaBase::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CuentaBase::class, 'parent_id');
    }

    public function parentRecursive(): BelongsTo
    {
        return $this->parent()->with('parentRecursive');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }
}