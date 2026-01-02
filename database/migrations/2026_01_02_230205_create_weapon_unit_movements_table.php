<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weapon_unit_movements', function (Blueprint $table) {
            $table->id();

            // Relación a la unidad física (serial)
            $table->foreignId('weapon_unit_id')
                ->constrained('weapon_units')
                ->cascadeOnDelete();

            // Tipo de movimiento
            $table->enum('type', ['IN', 'OUT']);

            // Datos administrativos
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            // Fecha del movimiento (no siempre es "now")
            $table->dateTime('moved_at')->default(now());

            // Usuario que realizó la acción
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Índices útiles para reportes / kardex
            $table->index(['weapon_unit_id', 'type']);
            $table->index(['type', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weapon_unit_movements');
    }
};
