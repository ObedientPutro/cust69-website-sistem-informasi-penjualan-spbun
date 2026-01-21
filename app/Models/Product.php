<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock' => 'decimal:2',
    ];

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
}
