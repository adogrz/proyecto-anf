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
        Schema::create('ratios_sector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained('sectores')->onDelete('cascade');
            $table->string('nombre_ratio');
            $table->decimal('valor_referencia', 15, 4);
            $table->string('fuente', 200)->nullable();
            $table->timestamps();

            $table->unique(['sector_id', 'nombre_ratio'], 'unique_ratio_sector');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratios_sector');
    }
};
