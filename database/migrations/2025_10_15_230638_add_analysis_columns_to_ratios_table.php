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
        Schema::table('ratios', function (Blueprint $table) {
            $table->text('mensaje_superior')->nullable()->after('valor');
            $table->text('mensaje_inferior')->nullable()->after('mensaje_superior');
            $table->text('mensaje_igual')->nullable()->after('mensaje_inferior');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ratios', function (Blueprint $table) {
            $table->dropColumn(['mensaje_superior', 'mensaje_inferior', 'mensaje_igual']);
        });
    }
};
