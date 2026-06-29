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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('id')
                ->constrained('branches')
                ->nullOnDelete();
        });

        Schema::table('weapon_units', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('weapon_id')
                ->constrained('branches')
                ->nullOnDelete();

            $table->index(['branch_id', 'status']);
        });

        Schema::table('ammo_movements', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('ammo_id')
                ->constrained('branches')
                ->nullOnDelete();

            $table->index(['branch_id', 'type']);
        });

        Schema::table('accessory_movements', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('accessory_id')
                ->constrained('branches')
                ->nullOnDelete();

            $table->index(['branch_id', 'type']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('branches')
                ->nullOnDelete();

            $table->index(['branch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('accessory_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('ammo_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('weapon_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
