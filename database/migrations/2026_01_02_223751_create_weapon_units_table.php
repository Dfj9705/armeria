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
        Schema::create('weapon_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weapon_id')->constrained('weapons')->cascadeOnDelete();

            $table->string('serial_number')->unique();
            $table->string('status')->default('IN_STOCK');

            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['weapon_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weapon_units');
    }
};
