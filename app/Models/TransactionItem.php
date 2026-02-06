<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'quantity_liter',
        'price_per_liter',
        'cost_per_liter',
        'subtotal',
    ];

    protected $casts = [
        'quantity_liter' => 'float',
        'price_per_liter' => 'float',
        'cost_per_liter' => 'float',
        'subtotal' => 'float',
    ];

    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (int) round($value),
        );
    }

    protected function pricePerLiter(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (int) round($value),
        );
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
