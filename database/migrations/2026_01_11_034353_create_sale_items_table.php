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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();

            // Polimórfico: WeaponUnit / Ammo / Accessory
            $table->morphs('sellable'); // sellable_type, sellable_id

            // qty:
            // - WeaponUnit: 1
            // - Accessory: quantity
            // - Ammo: puedes usar qty para “cantidad facturada” y en meta poner boxes/rounds
            $table->decimal('qty', 12, 3)->default(1);

            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);

            // Snapshot FEL (no dependas del catálogo después)
            $table->string('description_snapshot', 255);
            $table->string('uom_snapshot', 20)->default('UNI'); // UNI, CJ, etc.

            // meta:
            // Ammo: {"boxes":2,"rounds":null} o {"boxes":null,"rounds":50}
            // WeaponUnit: {"serial":"ABC123","weapon_id":5}
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
