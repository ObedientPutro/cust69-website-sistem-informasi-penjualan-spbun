<?php

use App\Enums\PaymentStatusEnum;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('trx_code', 30)->unique();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->dateTime('transaction_date');

            // mungkin ada case transaksi admin diluar shift (jarang, tapi jaga-jaga)
            $table->foreignId('pump_shift_id')->nullable()->constrained()->nullOnDelete();

            // Pembayaran
            $table->string('payment_method');
            $table->string('payment_status');
            $table->string('payment_proof')->nullable();
            $table->string('repayment_method')->nullable();
            $table->dateTime('paid_at')->nullable();

            // Total Rupiah
            $table->decimal('grand_total', 15, 2);

            // Audit Trail
            $table->text('note')->nullable();

            $table->timestamps();

            // Indexing untuk performa laporan
            $table->index(['transaction_date', 'payment_status', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
