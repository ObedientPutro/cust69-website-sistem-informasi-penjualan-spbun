<?php

namespace App\Services;

use App\Enums\PumpShiftStatusEnum;
use App\Models\PumpShift;
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
     * Menutup Shift (Sore/Akhir)
     */
    public function closeShift(PumpShift $shift, array $data): PumpShift
    {
        return DB::transaction(function () use ($shift, $data) {
            if ($data['closing_totalizer'] < $shift->opening_totalizer) {
                throw ValidationException::withMessages([
                    'closing_totalizer' => "Angka Akhir ({$data['closing_totalizer']}) tidak boleh lebih kecil dari Awal ({$shift->opening_totalizer})."
                ]);
            }

            $literSold = $data['closing_totalizer'] - $shift->opening_totalizer;

            $proofPath = null;
            if (isset($data['closing_proof']) && $data['closing_proof'] instanceof UploadedFile) {
                $proofPath = $data['closing_proof']->store('shifts/closing', 'public');
            }

            // Update Data
            $shift->update([
                'closed_by' => Auth::id(),
                'closing_totalizer' => $data['closing_totalizer'],
                'closing_proof' => $proofPath,
                'cash_collected' => $data['cash_collected'],
                'closed_at' => Carbon::now(),
                'total_sales_liter' => $literSold,
                'status' => PumpShiftStatusEnum::CLOSED->value,
            ]);

            NotificationHelper::send(
                'Shift Ditutup',
                Auth::user()->name . " menutup shift {$shift->product->name}. Total Liter: " . number_format($literSold) . "L. Cash: Rp " . number_format($data['cash_collected']),
                route('shifts.index'),
                'warning'
            );

            return $shift;
        });
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
