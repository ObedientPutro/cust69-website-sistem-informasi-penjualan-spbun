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
        Schema::create('restocks', function (Blueprint $table) {
            $table->id();

            // Relasi
            $table->foreignId('user_id')->constrained()->comment('Siapa yang input');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->date('date')->comment('Tanggal DO Masuk');

            // Data Keuangan & Volume
            $table->decimal('volume_liter', 15, 2);
            $table->decimal('total_cost', 15, 2)->comment('Total Rupiah ditebus');
            $table->decimal('unit_cost', 15, 2)->comment('Total / Volume (Snapshot HPP)');

            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restocks');
    }
};
