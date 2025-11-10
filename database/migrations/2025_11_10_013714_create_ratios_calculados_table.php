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
        Schema::create('ratios_calculados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->unsignedSmallInteger('anio');
            $table->string('nombre_ratio', 100);
            $table->decimal('valor_ratio', 15, 4)->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'anio', 'nombre_ratio'], 'unique_ratio_calculado');

            $table->index(['empresa_id', 'anio']);
            $table->index('nombre_ratio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratios_calculados');
    }
};
