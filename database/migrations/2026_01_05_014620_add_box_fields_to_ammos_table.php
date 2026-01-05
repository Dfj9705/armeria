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
        Schema::table('ammos', function (Blueprint $table) {
            $table->decimal('price_per_box', 12, 2)->nullable()->after('description');
            $table->unsignedInteger('rounds_per_box')->default(50)->after('price_per_box');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ammos', function (Blueprint $table) {
            $table->dropColumn('price_per_box');
            $table->dropColumn('rounds_per_box');
        });
    }
};
