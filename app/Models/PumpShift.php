<?php

namespace App\Models;

use App\Enums\PumpShiftStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class PumpShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'user_id',
        'product_id',
        'opening_totalizer',
        'opening_proof',
        'opened_at',
        'closing_totalizer',
        'closing_proof',
        'cash_collected',
        'closed_at',
        'total_sales_liter',
        'system_transaction_liter',
        'system_transaction_amount',
        'owner_note',
        'is_audited',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_totalizer' => 'decimal:2',
        'closing_totalizer' => 'decimal:2',
        'cash_collected' => 'decimal:2',
        'total_sales_liter' => 'decimal:2',
        'is_audited' => 'boolean',
        'status' => PumpShiftStatusEnum::class,
    ];

    // --- Accessors untuk URL Foto ---
    protected $appends = ['opening_proof_url', 'closing_proof_url'];

    public function getOpeningProofUrlAttribute(): ?string
    {
        return $this->opening_proof ? Storage::url($this->opening_proof) : null;
    }

    public function getClosingProofUrlAttribute(): ?string
    {
        return $this->closing_proof ? Storage::url($this->closing_proof) : null;
    }

    // --- Relationships ---
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
