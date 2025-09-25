<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DELETE mc1 FROM medidas_control mc1
            INNER JOIN medidas_control mc2
                ON mc1.id_puesto_trabajo_matriz = mc2.id_puesto_trabajo_matriz
                AND mc1.id_medidas_control > mc2.id_medidas_control');

        Schema::table('medidas_control', function (Blueprint $table) {
            $table->unique('id_puesto_trabajo_matriz', 'medidas_control_puesto_unique');
        });
    }

    public function down(): void
    {
        Schema::table('medidas_control', function (Blueprint $table) {
            $table->dropUnique('medidas_control_puesto_unique');
        });
    }
};

