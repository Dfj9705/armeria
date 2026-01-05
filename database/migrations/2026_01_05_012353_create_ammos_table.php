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
        Schema::create('ammos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('brand_id')->constrained('brands')->restrictOnDelete();
            $table->foreignId('caliber_id')->constrained('calibers')->restrictOnDelete();

            $table->string('name')->nullable(); // opcional: “FMJ 124gr”, “Hollow Point”, etc.
            $table->text('description')->nullable();

            $table->decimal('price', 12, 2)->nullable(); // precio referencial por unidad o caja (definimos después)
            $table->json('images')->nullable(); // para varias imágenes

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['brand_id', 'caliber_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ammos');
    }
};
