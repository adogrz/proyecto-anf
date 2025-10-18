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
        Schema::create('catalogos_cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('codigo_cuenta');
            $table->string('nombre_cuenta');
            $table->foreignId('cuenta_base_id')->nullable()->constrained('cuentas_base')->onDelete('set null');
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo_cuenta']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogos_cuentas');
    }
};
