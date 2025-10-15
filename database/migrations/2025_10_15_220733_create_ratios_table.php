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
        Schema::create('ratios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained('sectores')->onDelete('cascade');
            $table->string('nombre_ratio');
            $table->string('tipo_ratio')->default('estandar_sector'); // Ej: estandar_sector, promedio_sistema
            $table->decimal('valor', 8, 4);
            $table->timestamps();

            $table->unique(['sector_id', 'nombre_ratio', 'tipo_ratio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratios');
    }
};
