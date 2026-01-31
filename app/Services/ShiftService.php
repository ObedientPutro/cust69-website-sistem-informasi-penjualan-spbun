<?php

namespace App\Services;

use App\Enums\PumpShiftStatusEnum;
use App\Models\PumpShift;
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
            // 1. CEK GEMBOK: Apakah produk ini sedang dipakai orang lain?
            $activeShift = PumpShift::where('product_id', $data['product_id'])
                ->where('status', PumpShiftStatusEnum::OPEN)
                ->with('user')
                ->first();

            if ($activeShift) {
                // Walaupun beda hari, tetap tidak boleh buka kalau belum diclose
                throw ValidationException::withMessages([
                    'product_id' => "Pompa ini sedang digunakan oleh {$activeShift->user->name}. Harap minta ybs menutup shift terlebih dahulu."
                ]);
            }

            // 2. Handle Upload Foto
            $proofPath = null;
            if (isset($data['opening_proof']) && $data['opening_proof'] instanceof UploadedFile) {
                $proofPath = $data['opening_proof']->store('shifts/opening', 'public');
            }

            // 3. Create Shift
            return PumpShift::create([
                'date' => Carbon::now(),
                'user_id' => Auth::id(),
                'product_id' => $data['product_id'],
                'opening_totalizer' => $data['opening_totalizer'],
                'opening_proof' => $proofPath,
                'opened_at' => Carbon::now(),
                'status' => PumpShiftStatusEnum::OPEN,
            ]);
        });
    }

    /**
     * Menutup Shift (Sore/Akhir)
     */
    public function closeShift(PumpShift $shift, array $data): PumpShift
    {
        return DB::transaction(function () use ($shift, $data) {
            // 1. Validasi Totalisator
            // Angka Tutup TIDAK BOLEH LEBIH KECIL dari Angka Buka (Kecuali reset mesin, kasus khusus)
            if ($data['closing_totalizer'] < $shift->opening_totalizer) {
                throw ValidationException::withMessages([
                    'closing_totalizer' => 'Totalisator Akhir tidak boleh lebih kecil dari Awal (' . $shift->opening_totalizer . ').'
                ]);
            }

            // 2. Hitung Penjualan Liter Real (Selisih Meteran)
            $literSold = $data['closing_totalizer'] - $shift->opening_totalizer;

            // 3. Hitung Data Sistem (Dari Transaksi POS hari ini yg masuk shift ini)
            // Note: Nanti di TransactionService kita akan link kan trx ke shift ini
            $systemLiter = $shift->transactions()->sum('items.quantity_liter') ?? 0; // Perlu join logic di model nanti
            // Untuk sementara kita ambil simple sum, nanti diperbaiki di Transaction Integration

            // 4. Handle Foto Tutup
            $proofPath = null;
            if (isset($data['closing_proof']) && $data['closing_proof'] instanceof UploadedFile) {
                $proofPath = $data['closing_proof']->store('shifts/closing', 'public');
            }

            // 5. Update Shift
            $shift->update([
                'closing_totalizer' => $data['closing_totalizer'],
                'closing_proof' => $proofPath,
                'cash_collected' => $data['cash_collected'],
                'closed_at' => Carbon::now(),
                'total_sales_liter' => $literSold, // Realita lapangan
                // 'system_transaction_liter' => ... (Nanti diisi job/observer atau saat close ini query trx)
                'status' => PumpShiftStatusEnum::CLOSED,
            ]);

            return $shift;
        });
    }

    /**
     * Cek apakah Operator saat ini punya shift aktif?
     */
    public function getCurrentActiveShift()
    {
        return PumpShift::where('user_id', Auth::id())
            ->where('status', PumpShiftStatusEnum::OPEN)
            ->with('product')
            ->first();
    }
}
