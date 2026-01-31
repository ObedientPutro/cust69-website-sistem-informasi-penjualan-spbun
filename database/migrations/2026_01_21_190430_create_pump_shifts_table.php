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
        Schema::create('pump_shifts', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('product_id')->constrained();

            // --- FASE 1: BUKA SHIFT (OPEN) ---
            $table->decimal('opening_totalizer', 15, 2);
            $table->string('opening_proof')->nullable();
            $table->timestamp('opened_at');

            // --- FASE 2: TUTUP SHIFT (CLOSE) ---
            $table->decimal('closing_totalizer', 15, 2)->nullable();
            $table->string('closing_proof')->nullable();
            $table->decimal('cash_collected', 15, 2)->nullable();
            $table->timestamp('closed_at')->nullable();

            // --- FASE 3: REKONSILIASI SISTEM (AUTO) ---
            // Diisi sistem saat Closing
            $table->decimal('total_sales_liter', 15, 2)->default(0);
            $table->decimal('system_transaction_liter', 15, 2)->default(0);
            $table->decimal('system_transaction_amount', 15, 2)->default(0);

            // --- FASE 4: AUDIT OWNER (MANUAL) ---
            $table->text('owner_note')->nullable();
            $table->boolean('is_audited')->default(false);

            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pump_shifts');
    }
};
