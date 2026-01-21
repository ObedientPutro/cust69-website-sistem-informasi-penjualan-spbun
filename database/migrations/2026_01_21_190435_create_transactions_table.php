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

            $table->foreignId('user_id')->constrained();
            // Customer boleh null jika cash
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

            // Waktu Transaksi (Penting untuk Backdate Owner)
            $table->dateTime('transaction_date');

            // Pembayaran
            $table->string('payment_method');
            $table->string('payment_status')->default(PaymentStatusEnum::PAID->value);
            $table->string('payment_proof')->nullable()->comment('Path gambar bukti transfer');

            // Total Rupiah
            $table->decimal('grand_total', 15, 2);

            // Audit Trail
            $table->boolean('was_stock_minus')->default(false)->comment('Flag jika stok minus saat input');
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
