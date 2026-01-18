<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {

            $table->dateTime('fel_fecha_hora_emision')->nullable()->after('fel_status');
            $table->dateTime('fel_fecha_hora_certificacion')->nullable();
            $table->string('fel_nombre_receptor')->nullable();
            $table->string('fel_estado_documento')->nullable();
            $table->string('fel_nit_certificador')->nullable();
            $table->string('fel_nombre_certificador')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('fel_fecha_hora_emision');
            $table->dropColumn('fel_fecha_hora_certificacion');
            $table->dropColumn('fel_nombre_receptor');
            $table->dropColumn('fel_estado_documento');
            $table->dropColumn('fel_nit_certificador');
            $table->dropColumn('fel_nombre_certificador');
        });
    }
};
