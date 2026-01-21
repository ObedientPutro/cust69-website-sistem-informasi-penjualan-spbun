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
        Schema::create('tank_soundings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->dateTime('recorded_at');

            // Data Audit
            $table->decimal('physical_height_cm', 10, 2)->nullable();
            $table->decimal('physical_liter', 15, 2)->comment('Hasil Ukur Manual');
            $table->decimal('system_liter_snapshot', 15, 2)->comment('Stok App saat diukur');

            // Selisih (Losses/Gain)
            $table->decimal('difference', 10, 2)->comment('Physical - System');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tank_soundings');
    }
};
