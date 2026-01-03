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
        Schema::create('weapon_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();      // Pistola, Rifle, Escopeta
            $table->string('code')->nullable();    // PISTOL, RIFLE, SHOTGUN (opcional)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weapon_types');
    }
};
