<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'price',
        'cost_price',
        'stock',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'cost_price' => 'float',
        'stock' => 'float',
        'is_active' => 'boolean',
    ];

    // Harga Jual (Rp)
    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (int) round($value),
        );
    }

    // HPP (Rp)
    protected function costPrice(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (int) round($value),
        );
    }

    // --- Relationships ---
    public function restocks(): HasMany
    {
        return $this->hasMany(Restock::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function tankSoundings(): HasMany
    {
        return $this->hasMany(TankSounding::class);
    }

    public function productPriceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    public function productPumpShiftHistories(): HasMany
    {
        return $this->hasMany(PumpShift::class);
    }

}
