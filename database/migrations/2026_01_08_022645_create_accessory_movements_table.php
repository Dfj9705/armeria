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
        Schema::create('accessory_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('accessory_id')->constrained('accessories')->cascadeOnDelete();

            // in = ingreso, out = egreso
            $table->enum('type', ['in', 'out'])->index();
            $table->unsignedInteger('quantity');

            // costo unitario del movimiento (para valorización, opcional)
            $table->decimal('unit_cost', 10, 2)->nullable();

            $table->dateTime('occurred_at')->index();
            $table->string('reference', 120)->nullable(); // factura, vale, etc.
            $table->text('notes')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['accessory_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accessory_movements');
    }
};
