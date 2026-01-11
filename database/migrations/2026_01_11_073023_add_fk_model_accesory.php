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
        Schema::table('accessories', function (Blueprint $table) {
            $table->foreignId('compatible_brand_model_id')
                ->nullable()
                ->constrained('brand_models')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accessories', function (Blueprint $table) {
            $table->dropForeign(['compatible_brand_model_id']);
            $table->dropIndex(['compatible_brand_model_id']);
            $table->dropColumn('compatible_brand_model_id');
        });
    }
};
