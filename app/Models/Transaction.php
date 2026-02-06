<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'trx_code',
        'user_id',
        'customer_id',
        'pump_shift_id',
        'transaction_date',
        'payment_method',
        'payment_status',
        'payment_proof',
        'repayment_method',
        'paid_at',
        'grand_total',
        'was_stock_minus',
        'note',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'paid_at' => 'datetime',
        'grand_total' => 'float',
        'was_stock_minus' => 'boolean',
        'payment_method' => PaymentMethodEnum::class,
        'repayment_method' => PaymentMethodEnum::class,
        'payment_status' => PaymentStatusEnum::class,
    ];

    protected $appends = ['payment_proof_url'];

    public function getPaymentProofUrlAttribute(): ?string
    {
        return $this->payment_proof ? Storage::url($this->payment_proof) : null;
    }

    /**
     * Scope: Hanya ambil transaksi yang VALID (Tidak Void/Returned).
     */
    public function scopeValid(Builder $query): void
    {
        $query->where('payment_status', '!=', PaymentStatusEnum::RETURNED->value);
    }

    /**
     * Scope: Ambil transaksi milik Shift tertentu.
     */
    public function scopeForShift(Builder $query, PumpShift $shift): void
    {
        $query->where(function ($q) use ($shift) {
            $q->where('pump_shift_id', $shift->id)
                ->orWhere(function ($sq) use ($shift) {
                    $endTime = $shift->closed_at ?? Carbon::now();
                    $sq->whereBetween('transaction_date', [$shift->opened_at, $endTime])
                        ->whereHas('items', fn($i) => $i->where('product_id', $shift->product_id));
                });
        });
    }

    // --- Relationships ---
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function pumpShift(): BelongsTo
    {
        return $this->belongsTo(PumpShift::class);
    }
}
