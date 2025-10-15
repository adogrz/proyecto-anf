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
        Schema::create('proyecciones_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->year('anio');
            $table->unsignedTinyInteger('mes');
            $table->decimal('monto_ventas', 15, 2);
            $table->enum('tipo', ['historico', 'proyectado_minimos_cuadrados', 'proyectado_porcentual', 'proyectado_absoluto']);
            $table->timestamps();

            $table->unique(['empresa_id', 'anio', 'mes', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyecciones_ventas');
    }
};
