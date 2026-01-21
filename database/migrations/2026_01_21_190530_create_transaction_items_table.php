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
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();

            $table->decimal('quantity_liter', 10, 2);

            // SNAPSHOT PRICE (Penting untuk Laporan Historis)
            $table->decimal('price_per_liter', 15, 2)->comment('Harga Jual saat itu');
            $table->decimal('cost_per_liter', 15, 2)->comment('Harga Modal saat itu (Hidden from operator)');

            $table->decimal('subtotal', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
