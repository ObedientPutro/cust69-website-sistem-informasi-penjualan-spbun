<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
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
}
