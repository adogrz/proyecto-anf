<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    use HasFactory;

    protected $table = 'sectores';

    protected $fillable = ['nombre', 'descripcion'];

    // Relaciones
    public function ratios(): HasMany
    {
        return $this->hasMany(RatioSector::class, 'sector_id');
    }
}
