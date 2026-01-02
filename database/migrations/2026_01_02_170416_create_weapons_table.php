<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weapons', function (Blueprint $table) {
            $table->id();

            $table->string('serial_number')->unique()->nullable(); // recomendado
            $table->string('brand');
            $table->string('model');
            $table->string('caliber'); // ejemplo: "9mm", ".45 ACP"
            $table->unsignedSmallInteger('magazine_capacity')->nullable();
            $table->unsignedSmallInteger('barrel_length_mm')->nullable();

            $table->decimal('price', 12, 2)->default(0);

            $table->string('status')->default('ACTIVE'); // ACTIVE, INACTIVE, MAINTENANCE
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weapons');
    }
};
