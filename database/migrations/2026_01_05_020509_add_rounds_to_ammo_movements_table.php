<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ammo_movements', function (Blueprint $table) {
                // cajas ya no es obligatoria (porque puede ser suelta)
            $table->unsignedInteger('boxes')->nullable()->change();

            // cartuchos sueltos
            $table->unsignedInteger('rounds')->nullable()->after('boxes');
        });
    }

    public function down(): void
    {
        Schema::table('ammo_movements', function (Blueprint $table) {
            $table->dropColumn('rounds');
            $table->unsignedInteger('boxes')->nullable(false)->change();
        });
    }
};
