<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DebtService
{
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
