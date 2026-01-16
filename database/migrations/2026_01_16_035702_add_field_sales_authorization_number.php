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
        Schema::table("sale_items", function (Blueprint $table) {
            $table->string("authorization_number")->nullable()->after("sellable_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("sale_items", function (Blueprint $table) {
            $table->dropColumn("authorization_number");
        });
    }
};
