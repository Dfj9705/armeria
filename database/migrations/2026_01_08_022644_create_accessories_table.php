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
        Schema::create('accessories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')->constrained('accessory_categories');
            $table->foreignId('brand_id')->nullable()->constrained('brands'); // brands.type = accessory
            $table->string('name', 120);
            $table->string('sku', 60)->nullable()->unique();
            $table->text('description')->nullable();

            // Precios de referencia (opcionales)
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();

            $table->unsignedInteger('stock_min')->default(0);
            $table->boolean('is_active')->default(true);

            // Si quieres trazabilidad:
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['category_id', 'brand_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accessories');
    }
};
