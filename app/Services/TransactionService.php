<?php

namespace App\Services;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function createTransaction(array $data, $fileProof = null): Transaction
    {
        return DB::transaction(function () use ($data, $fileProof) {
            $user = Auth::user();

            $grandTotal = 0;
            $itemsPayload = [];

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $subtotal = $item['quantity_liter'] * $product->price;
                $grandTotal += $subtotal;

                $itemsPayload[] = [
                    'product' => $product,
                    'quantity_liter' => $item['quantity_liter'],
                    'price_per_liter' => $product->price,
                    'cost_per_liter' => $product->cost_price,
                    'subtotal' => $subtotal,
                ];
            }

            if ($data['payment_method'] === PaymentMethodEnum::BON->value) {
                $customer = Customer::where('id', $data['customer_id'])->lockForUpdate()->firstOrFail();

                if (!$customer->is_active) {
                    throw ValidationException::withMessages([
                        'customer_id' => 'Akun pelanggan ini sedang dinonaktifkan (Frozen). Hubungi Admin.'
                    ]);
                }

                if ($customer->credit_limit < $grandTotal) {
                    $sisa = number_format($customer->credit_limit, 0, ',', '.');
                    $tagihan = number_format($grandTotal, 0, ',', '.');

                    throw ValidationException::withMessages([
                        'customer_id' => "Limit kredit tidak mencukupi! Sisa: Rp {$sisa}, Tagihan: Rp {$tagihan}."
                    ]);
                }

                $customer->decrement('credit_limit', $grandTotal);
            }

            if ($user->role !== UserRoleEnum::OWNER->value) {
                $transactionDate = Carbon::now();
            } else {
                $inputDate = Carbon::parse($data['transaction_date']);
                $transactionDate = $inputDate->setTimeFrom(Carbon::now());
            }

            $proofPath = null;
            if ($data['payment_method'] === PaymentMethodEnum::TRANSFER->value && $fileProof) {
                $proofPath = $fileProof->store('payment_proofs', 'public');
            }

            $paymentStatus = ($data['payment_method'] === PaymentMethodEnum::BON->value)
                ? PaymentStatusEnum::UNPAID
                : PaymentStatusEnum::PAID;

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'customer_id' => $data['customer_id'] ?? null,
                'transaction_date' => $transactionDate,
                'payment_method' => $data['payment_method'],
                'payment_status' => $paymentStatus,
                'payment_proof' => $proofPath,
                'grand_total' => $grandTotal,
                'was_stock_minus' => collect($itemsPayload)->contains(fn($i) => $i['product']->stock < 0), // Cek stok minus
                'note' => $data['note'] ?? null,
            ]);

            foreach ($itemsPayload as $payload) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $payload['product']->id,
                    'quantity_liter' => $payload['quantity_liter'],
                    'price_per_liter' => $payload['price_per_liter'],
                    'cost_per_liter' => $payload['cost_per_liter'],
                    'subtotal' => $payload['subtotal'],
                ]);

                $payload['product']->decrement('stock', $payload['quantity_liter']);
            }

            return $transaction;
        });
    }

    /**
     * Handle Pelunasan Hutang (Bon)
     */
    public function processRepayment(Transaction $transaction, array $data, ?UploadedFile $fileProof = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $data, $fileProof) {
            if ($transaction->payment_status === PaymentStatusEnum::PAID) {
                throw ValidationException::withMessages(['payment_status' => 'Transaksi ini sudah lunas.']);
            }

            $updateData = [
                'payment_status'   => PaymentStatusEnum::PAID,
                'repayment_method' => $data['repayment_method'],
                'paid_at'          => Carbon::now(),
            ];

            if ($fileProof) {
                $updateData['payment_proof'] = $fileProof->store('repayment_proofs', 'public');
            }

            $transaction->update($updateData);

            if ($transaction->customer_id) {
                $transaction->customer()->increment('credit_limit', $transaction->grand_total);
            }

            return $transaction;
        });
    }
}
