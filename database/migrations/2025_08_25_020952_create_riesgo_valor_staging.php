<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_riesgo_valor_staging.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('riesgo_valor_staging', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_puesto_trabajo_matriz');
            $table->unsignedInteger('id_riesgo');
            $table->string('valor', 50)->nullable();
            $table->string('observaciones', 1000)->nullable();
            $table->char('import_token', 36);     // agrupa un solo import
            $table->timestamps();

            $table->unique(['import_token','id_puesto_trabajo_matriz','id_riesgo'], 'uq_stage');
            $table->index('import_token');
        });
    }
    public function down(): void {
        Schema::dropIfExists('riesgo_valor_staging');
    }
};
