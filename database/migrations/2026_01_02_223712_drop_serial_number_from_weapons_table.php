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
        Schema::table('weapons', function (Blueprint $table) {
            if (Schema::hasColumn('weapons', 'serial_number')) {
                $table->dropUnique(['serial_number']); // si existe el unique
                $table->dropColumn('serial_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('weapons', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->unique();
        });
    }
};
