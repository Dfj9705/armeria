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
            $table->foreignId('brand_id')->nullable()->after('serial_number')->constrained('brands')->nullOnDelete();
            $table->foreignId('brand_model_id')->nullable()->after('brand_id')->constrained('brand_models')->nullOnDelete();

            $table->dropColumn(['brand', 'model']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weapons', function (Blueprint $table) {
            //
        });
    }
};
