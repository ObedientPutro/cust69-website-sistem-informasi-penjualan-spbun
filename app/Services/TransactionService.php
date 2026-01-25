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

    /**
     * Get Transaction History dengan Filter Lengkap
     */
    public function getHistory(array $filters, int $perPage = 15)
    {
        $query = Transaction::with(['user', 'customer', 'items.product']);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('transaction_date', [
                $filters['start_date'] . ' 00:00:00',
                $filters['end_date'] . ' 23:59:59'
            ]);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%") // Asumsi ID sebagai No Ref
                ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if (!empty($filters['product_id'])) {
            $query->whereHas('items', function($q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            });
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        return $query->latest('transaction_date');
    }
}
