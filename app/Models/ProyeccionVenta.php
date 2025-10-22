<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProyeccionVenta extends Model
{
    public const METODO_GENERACION_MINIMOS_CUADRADOS = 'MINIMOS_CUADRADOS';
    public const METODO_GENERACION_INCREMENTO_PORCENTUAL = 'INCREMENTO_PORCENTUAL';
    public const METODO_GENERACION_INCREMENTO_ABSOLUTO = 'INCREMENTO_ABSOLUTO';

    protected $table = 'proyeccion_ventas';

    protected $with = ['ejecucion'];

    protected $fillable = [
        'ejecucion_id',
        'metodo',
        'anio',
        'mes',
        'monto',
    ];

    public function ejecucion(): BelongsTo
    {
        return $this->belongsTo(EjecucionProyeccion::class, 'ejecucion_id');
    }

    public function scopeByMetodo($query, string $metodo): Builder
    {
        return $query->where('metodo', $metodo);
    }

    public function scopeByMinimosCuadrados($query): Builder
    {
        return $query->byMetodo(self::METODO_GENERACION_MINIMOS_CUADRADOS);
    }

    public function scopeByIncrementoPorcentual($query): Builder
    {
        return $query->byMetodo(self::METODO_GENERACION_INCREMENTO_PORCENTUAL);
    }

    public function scopeByIncrementoAbsoluto($query): Builder
    {
        return $query->byMetodo(self::METODO_GENERACION_INCREMENTO_ABSOLUTO);
    }

    protected function metodoLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->metodo) {
                self::METODO_GENERACION_MINIMOS_CUADRADOS => 'Mínimos Cuadrados',
                self::METODO_GENERACION_INCREMENTO_PORCENTUAL => 'Incremento Porcentual',
                self::METODO_GENERACION_INCREMENTO_ABSOLUTO => 'Incremento Absoluto',
                default => 'Desconocido',
            },
        );
    }
}
