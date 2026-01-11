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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);   // IVA 12%
            $table->decimal('total', 12, 2)->default(0);

            $table->timestamp('confirmed_at')->nullable();

            // FEL (TEKRA) - se llenan después
            $table->string('fel_uuid', 80)->nullable()->index();
            $table->string('fel_serie', 50)->nullable();
            $table->string('fel_numero', 50)->nullable();
            $table->enum('fel_status', ['pending', 'certified', 'error', 'void'])->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
