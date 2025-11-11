<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlantillaCatalogo extends Model
{
    use HasFactory;

    protected $table = 'plantillas_catalogo';

    protected $fillable = ['nombre', 'descripcion'];

    

        public function cuentasBase(): HasMany

        {

            return $this->hasMany(CuentaBase::class);

        }

    

        public function empresas(): HasMany

        {

            return $this->hasMany(Empresa::class);

        }

    }

    