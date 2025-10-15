<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaBase extends Model
{
    use HasFactory;

    protected $table = 'cuentas_base';

    protected $fillable = ['mapa_sistema', 'nombre', 'tipo_cuenta', 'descripcion'];
}
