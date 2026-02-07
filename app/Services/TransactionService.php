<?php

namespace App\Services;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PumpShift;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Traits\NotificationHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    /**
     * @throws \Throwable
     */
    public function createTransaction(array $data, $fileProof = null): Transaction
    {
        return DB::transaction(function () use ($data, $fileProof) {
            $user = Auth::user();
            $trxCode = $this->generateTrxCode();

            $grandTotal = 0;
            $itemsPayload = [];

            $firstProductId = $data['items'][0]['product_id'];
            $pumpShiftId = $this->findActiveShiftId($firstProductId);

            foreach ($data['items'] as $item) {
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();

                if ($product->stock < $item['quantity_liter']) {
                    throw ValidationException::withMessages([
                        'items.0.quantity_liter' => "Stok tidak cukup. Sisa: {$product->stock} L, Diminta: {$item['quantity_liter']} L"
                    ]);
                }

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

            if ($data['payment_method'] == PaymentMethodEnum::BON->value) {
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

            $transactionDate = ($user->role != UserRoleEnum::OWNER->value)
                ? Carbon::now()
                : Carbon::parse($data['transaction_date'])->setTimeFrom(Carbon::now());

            $proofPath = ($data['payment_method'] == PaymentMethodEnum::TRANSFER->value && $fileProof)
                ? $fileProof->store('payment_proofs', 'public')
                : null;

            $paymentStatus = ($data['payment_method'] == PaymentMethodEnum::BON->value)
                ? PaymentStatusEnum::UNPAID
                : PaymentStatusEnum::PAID;

            $transaction = Transaction::create([
                'trx_code' => $trxCode,
                'user_id' => $user->id,
                'customer_id' => $data['customer_id'] ?? null,
                'pump_shift_id' => $pumpShiftId,
                'transaction_date' => $transactionDate,
                'payment_method' => $data['payment_method'],
                'payment_status' => $paymentStatus,
                'payment_proof' => $proofPath,
                'grand_total' => $grandTotal,
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

                $newStock = $payload['product']->stock;
                if ($newStock < 500) {
                    NotificationHelper::send(
                        'Stok Menipis!',
                        "Perhatian! Stok {$payload['product']->name} tersisa " . number_format($newStock) . " Liter.",
                        route('products.index'),
                        'error'
                    );
                }
            }

            NotificationHelper::send(
                'Transaksi Baru',
                "Penjualan ID #{$transaction->id} oleh " . Auth::user()->name . ". Total: Rp " . number_format($transaction->grand_total),
                route('transactions.create'),
                'success'
            );

            return $transaction;
        });
    }

    /**
     * UPDATE TRANSAKSI (HANYA OWNER)
     * @throws \Throwable
     */
    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // A. ROLLBACK: Kembalikan Stok & Limit Kredit Lama
            foreach ($transaction->items as $oldItem) {
                $oldItem->product->increment('stock', $oldItem->quantity_liter);
                $oldItem->delete(); // Hapus item lama
            }

            if ($transaction->payment_method === PaymentMethodEnum::BON && $transaction->customer_id) {
                // Kembalikan limit kredit pelanggan (karena nanti akan dipotong lagi dengan nominal baru)
                $transaction->customer->increment('credit_limit', $transaction->grand_total);
            }

            // B. PROSES ULANG: Hitung Data Baru
            $grandTotal = 0;

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $subtotal = $item['quantity_liter'] * $product->price;
                $grandTotal += $subtotal;

                // Create Item Baru
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity_liter' => $item['quantity_liter'],
                    'price_per_liter' => $product->price,
                    'cost_per_liter' => $product->cost_price,
                    'subtotal' => $subtotal,
                ]);

                // Potong Stok Baru
                $product->decrement('stock', $item['quantity_liter']);
            }

            // C. UPDATE DATA PELANGGAN (JIKA BON)
            if ($transaction->payment_method === PaymentMethodEnum::BON && $transaction->customer_id) {
                $transaction->customer->decrement('credit_limit', $grandTotal);
            }

            // D. UPDATE HEADER TRANSAKSI
            $transaction->update([
                'transaction_date' => Carbon::parse($data['transaction_date'])->setTimeFrom($transaction->transaction_date),
                'grand_total' => $grandTotal,
                'note' => $data['note'] ?? $transaction->note,
            ]);

            return $transaction;
        });
    }

    /**
     * Return Transaksi (Void)
     * @throws \Throwable
     */
    public function returnTransaction(Transaction $transaction, string $reason): Transaction
    {
        return DB::transaction(function () use ($transaction, $reason) {
            foreach ($transaction->items as $item) {
                $item->product->increment('stock', $item->quantity_liter);
            }

            if ($transaction->payment_method == PaymentMethodEnum::BON && $transaction->customer_id) {
                $transaction->customer->increment('credit_limit', $transaction->grand_total);
            }

            $transaction->update([
                'payment_status' => PaymentStatusEnum::RETURNED,
                'note' => $transaction->note . " [RETURNED: $reason]",
            ]);

            return $transaction;
        });
    }

    /**
     * Generate PDF Struk
     * Service yang mengembalikan object PDF stream
     */
    public function generateReceiptPdf(Transaction $transaction): \Barryvdh\DomPDF\PDF
    {
        $pdf = Pdf::loadView('exports.receipt_pdf', [
            'trx' => $transaction,
            'settings' => \App\Models\SiteSetting::first(),
        ]);

        $pdf->setPaper([0, 0, 226.77, 600], 'portrait');

        return $pdf;
    }

    /**
     * Helper: Sinkronisasi Total Shift
     */
    private function syncShiftSnapshot($shiftId): void
    {
        $shift = PumpShift::find($shiftId);

        if ($shift) {
            $validTransactions = Transaction::forShift($shift)
                ->valid()
                ->with('items')
                ->get();

            $totalLiter = $validTransactions->sum(function ($trx) use ($shift) {
                return $trx->items->where('product_id', $shift->product_id)->sum('quantity_liter');
            });

            $totalAmount = $validTransactions->sum('grand_total');

            $shift->update([
                'system_transaction_liter' => $totalLiter,
                'system_transaction_amount' => $totalAmount,
            ]);
        }
    }

    /**
     * Get Transaction History dengan Filter Lengkap
     */
    public function getHistory(array $filters, int $perPage = 15): Builder
    {
        $query = Transaction::with(['user', 'customer', 'items.product']);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('transaction_date', [
                $filters['start_date'] . ' 00:00:00',
                $filters['end_date'] . ' 23:59:59'
            ]);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['product_id'])) {
            $query->whereHas('items', function($q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($subQuery) use ($search) {
                $subQuery->where('trx_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function($q) use ($search) {
                        $q->where('ship_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Mencari Shift Aktif berdasarkan Produk
     */
    private function findActiveShiftId($productId)
    {
        $shift = PumpShift::where('product_id', $productId)
            ->where('status', 'open')
            ->latest()
            ->first();

        return $shift ? $shift->id : null;
    }

    /**
     * Generate Kode Transaksi Unik: TRX-YYMMDD-XXXX
     */
    private function generateTrxCode(): string
    {
        $dateCode = Carbon::now()->format('ymd');
        $lastTrx = Transaction::whereDate('created_at', Carbon::today())->latest()->first();

        if ($lastTrx && preg_match('/TRX-\d+-(\d+)/', $lastTrx->trx_code, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return 'TRX-' . $dateCode . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
