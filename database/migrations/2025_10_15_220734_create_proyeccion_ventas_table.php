<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proyeccion_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ejecucion_id')->constrained('ejecucion_proyecciones')->onDelete('cascade');
            $table->enum('metodo', ['MINIMOS_CUADRADOS', 'INCREMENTO_PORCENTUAL', 'INCREMENTO_ABSOLUTO']);
            $table->year('anio');
            $table->unsignedTinyInteger('mes');
            $table->decimal('monto', 15, 2);
            $table->timestamps();

            $table->unique(['ejecucion_id', 'metodo', 'anio', 'mes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyeccion_ventas');
    }
};
