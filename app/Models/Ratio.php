<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ratio extends Model
{
    use HasFactory;

    protected $table = 'ratios';

    protected $fillable = ['sector_id', 'nombre_ratio', 'valor', 'tipo_ratio'];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}
