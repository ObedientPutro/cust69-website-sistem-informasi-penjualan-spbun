<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Solar, Pertalite, Dexlite');
            $table->string('unit', 50)->default('Liter');
            $table->boolean('is_active')->default(true);

            // Harga Jual (Rupiah)
            $table->decimal('price', 15, 2)->default(0);

            // Harga Modal Terakhir (HPP)
            $table->decimal('cost_price', 15, 2)->default(0);

            // Stok (Boleh Minus / Signed)
            $table->decimal('stock', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
