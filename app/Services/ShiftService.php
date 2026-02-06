<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Enums\PumpShiftStatusEnum;
use App\Models\PumpShift;
use App\Models\Transaction;
use App\Traits\NotificationHelper;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    /**
     * Membuka Shift Baru (Pagi/Awal)
     * @throws \Throwable
     */
    public function openShift(array $data): PumpShift
    {
        return DB::transaction(function () use ($data) {
            $isActive = PumpShift::where('product_id', $data['product_id'])
                ->where('status', PumpShiftStatusEnum::OPEN)
                ->exists();

            if ($isActive) {
                throw ValidationException::withMessages([
                    'product_id' => 'Shift untuk produk ini masih berjalan (OPEN).'
                ]);
            }

            $proofPath = null;
            if (isset($data['opening_proof']) && $data['opening_proof'] instanceof UploadedFile) {
                $proofPath = $data['opening_proof']->store('shifts/opening', 'public');
            }

            $shift = PumpShift::create([
                'date' => Carbon::now(),
                'product_id' => $data['product_id'],
                'opened_by' => Auth::id(),
                'opening_totalizer' => $data['opening_totalizer'],
                'opening_proof' => $proofPath,
                'opened_at' => Carbon::now(),
                'status' => PumpShiftStatusEnum::OPEN->value,
            ]);

            NotificationHelper::send(
                'Shift Dibuka',
                Auth::user()->name . " baru saja membuka shift untuk produk {$shift->product->name} dengan meteran awal " . number_format($shift->opening_totalizer),
                route('shifts.index'),
                'success'
            );

            return $shift;
        });
    }

    /**
     * Menutup Shift & Melakukan Snapshot Data Sistem
     * @throws \Throwable
     */
    public function closeShift(PumpShift $shift, array $data): PumpShift
    {
        return DB::transaction(function () use ($shift, $data) {
            if ($data['closing_totalizer'] < $shift->opening_totalizer) {
                throw ValidationException::withMessages([
                    'closing_totalizer' => "Meteran akhir ({$data['closing_totalizer']}) tidak boleh lebih kecil dari awal ({$shift->opening_totalizer})."
                ]);
            }

            // 1. Hitung Fisik (Totalizer)
            $literSoldPhysical = $data['closing_totalizer'] - $shift->opening_totalizer;

            // 2. Hitung Sistem (Transaksi Valid Saja)
            $validTransactions = Transaction::forShift($shift)
                ->valid()
                ->with('items')
                ->get();

            // Hitung total liter dari transaksi sistem
            $literSoldSystem = $validTransactions->sum(fn($t) => $t->items->where('product_id', $shift->product_id)->sum('quantity_liter'));
            $amountSystem = $validTransactions->sum('grand_total');

            $proofPath = null;
            if (isset($data['closing_proof']) && $data['closing_proof'] instanceof UploadedFile) {
                $proofPath = $data['closing_proof']->store('shifts/closing', 'public');
            }

            // 3. Update Data Shift (Termasuk Snapshot Sistem)
            $shift->update([
                'closed_by' => Auth::id(),
                'closing_totalizer' => $data['closing_totalizer'],
                'closing_proof' => $proofPath,
                'cash_collected' => $data['cash_collected'],
                'closed_at' => Carbon::now(),

                'total_sales_liter' => $literSoldPhysical, // Fisik
                'system_transaction_liter' => $literSoldSystem, // Sistem
                'system_transaction_amount' => $amountSystem, // Uang Sistem

                'status' => PumpShiftStatusEnum::CLOSED->value,
                'is_audited' => false, // Reset audit status
            ]);

            // Cek Selisih untuk Notifikasi
            $diff = abs($literSoldPhysical - $literSoldSystem);
            $notifType = $diff > 1 ? 'error' : 'success'; // Jika selisih > 1 Liter anggap masalah
            $msg = "Shift Ditutup. Fisik: {$literSoldPhysical}L, Sistem: {$literSoldSystem}L. ";
            $msg .= ($diff > 1) ? "TERDAPAT SELISIH {$diff}L. PERLU AUDIT!" : "Data Cocok.";

            NotificationHelper::send('Laporan Shift', $msg, route('shifts.index'), $notifType);

            return $shift;
        });
    }

    /**
     * Method Baru: Audit Shift oleh Owner
     */
    public function auditShift(PumpShift $shift, string $note): PumpShift
    {
        $shift->update([
            'owner_note' => $note,
            'is_audited' => true
        ]);

        return $shift;
    }

    /**
     * Ambil semua Shift yang sedang OPEN untuk keperluan POS
     * Return: [product_id => shift_id]
     */
    public function getActiveShiftsMap(): \Illuminate\Support\Collection
    {
        return PumpShift::where('status', PumpShiftStatusEnum::OPEN)
            ->pluck('id', 'product_id');
    }

}
