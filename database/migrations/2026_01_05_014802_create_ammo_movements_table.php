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
        Schema::create('ammo_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ammo_id')->constrained('ammos')->cascadeOnDelete();

            $table->enum('type', ['IN', 'OUT']);

            $table->unsignedInteger('boxes');
            $table->decimal('unit_cost_box', 12, 2)->nullable();

            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('moved_at')->default(now());

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['ammo_id', 'type']);
            $table->index(['type', 'moved_at']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ammo_movements');
    }
};
