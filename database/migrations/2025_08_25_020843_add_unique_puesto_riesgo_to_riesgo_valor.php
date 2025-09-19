<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_unique_puesto_riesgo_to_riesgo_valor.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('riesgo_valor', function (Blueprint $table) {
            $table->unique(['id_puesto_trabajo_matriz','id_riesgo'], 'uq_puesto_riesgo');
        });
    }
    public function down(): void {
        Schema::table('riesgo_valor', function (Blueprint $table) {
            $table->dropUnique('uq_puesto_riesgo');
        });
    }
};