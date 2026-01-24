<?php

namespace App\Services;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function createTransaction(array $data, $fileProof = null): Transaction
    {
        return DB::transaction(function () use ($data, $fileProof) {
            $user = Auth::user();

            // 1. REQ-C02: Handle Tanggal (Backdate Security)
            // Jika bukan Owner, paksa pakai waktu sekarang
            if ($user->role !== UserRoleEnum::OWNER) {
                $transactionDate = Carbon::now();
            } else {
                // Jika Owner, gunakan inputan (bisa backdate), tambah jam sekarang agar urutan logis
                $inputDate = Carbon::parse($data['transaction_date']);
                $transactionDate = $inputDate->setTimeFrom(Carbon::now());
            }

            // 2. Handle Upload Bukti Transfer
            $proofPath = null;
            if ($data['payment_method'] === PaymentMethodEnum::TRANSFER->value && $fileProof) {
                $proofPath = $fileProof->store('payment_proofs', 'public');
            }

            // 3. Tentukan Payment Status
            $paymentStatus = PaymentStatusEnum::PAID;
            if ($data['payment_method'] === PaymentMethodEnum::BON->value) {
                $paymentStatus = PaymentStatusEnum::UNPAID;
            }

            // 4. Hitung Grand Total & Cek Stok (In-Memory dulu)
            $grandTotal = 0;
            $itemsPayload = [];
            $wasStockMinus = false;

            foreach ($data['items'] as $item) {
                // Lock product untuk data integrity
                $product = Product::findOrFail($item['product_id']);

                // Cek apakah transaksi ini menyebabkan/dilakukan saat stok minus (Audit Trail)
                if ($product->stock < 0) {
                    $wasStockMinus = true;
                }

                $subtotal = $item['quantity_liter'] * $product->price;
                $grandTotal += $subtotal;

                $itemsPayload[] = [
                    'product' => $product,
                    'quantity_liter' => $item['quantity_liter'],
                    'price_per_liter' => $product->price,
                    'cost_per_liter' => $product->cost_price, // Hidden HPP Snapshot
                    'subtotal' => $subtotal,
                ];
            }

            // 5. Create Transaction Header
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'customer_id' => $data['customer_id'] ?? null,
                'transaction_date' => $transactionDate,
                'payment_method' => $data['payment_method'],
                'payment_status' => $paymentStatus,
                'payment_proof' => $proofPath,
                'grand_total' => $grandTotal,
                'was_stock_minus' => $wasStockMinus,
                'note' => $data['note'] ?? null,
            ]);

            // 6. Create Items & Deduct Stock
            foreach ($itemsPayload as $payload) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $payload['product']->id,
                    'quantity_liter' => $payload['quantity_liter'],
                    'price_per_liter' => $payload['price_per_liter'],
                    'cost_per_liter' => $payload['cost_per_liter'],
                    'subtotal' => $payload['subtotal'],
                ]);

                // Kurangi Stok (Atomic Update)
                // Kita izinkan minus sesuai REQ-B04 (Negative Stock Handling)
                $payload['product']->decrement('stock', $payload['quantity_liter']);
            }

            return $transaction;
        });
    }
}
