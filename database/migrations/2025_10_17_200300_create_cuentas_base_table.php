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
        Schema::create('cuentas_base', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plantilla_catalogo_id')->constrained('plantillas_catalogo')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('cuentas_base')->onDelete('cascade');
            $table->string('codigo');
            $table->string('nombre');
            $table->enum('tipo_cuenta', ['AGRUPACION', 'DETALLE']);
            $table->enum('naturaleza', ['DEUDORA', 'ACREEDORA']);
            $table->timestamps();

            $table->unique(['plantilla_catalogo_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_base');
    }
};