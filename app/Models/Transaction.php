<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
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
        'grand_total' => 'decimal:2',
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
