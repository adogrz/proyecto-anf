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
        Schema::create('detalles_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_financiero_id')->constrained('estados_financieros')->onDelete('cascade');
            $table->foreignId('catalogo_cuenta_id')->constrained('catalogos_cuentas')->onDelete('cascade');
            $table->decimal('valor', 15, 2);
            $table->timestamps();

            $table->unique(['estado_financiero_id', 'catalogo_cuenta_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_estados');
    }
};
