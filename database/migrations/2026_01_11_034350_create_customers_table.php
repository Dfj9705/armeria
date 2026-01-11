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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // Datos generales
            $table->string('name', 150);                 // Nombre "interno"
            $table->string('tax_name', 150)->nullable(); // Nombre receptor para FEL (si difiere)
            $table->string('email', 150)->nullable();
            $table->string('phone', 50)->nullable();

            // Identificación (separado)
            $table->string('nit', 20)->nullable()->index();
            $table->string('cui', 20)->nullable()->index();

            // Dirección para FEL
            $table->string('address', 255)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->nullOnDelete();


            $table->boolean('is_active')->default(true);

            // Auditoría (igual que tu estilo)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Evita duplicados
            $table->unique(['nit']);
            $table->unique(['cui']);
            $table->index(['department_id', 'municipality_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
