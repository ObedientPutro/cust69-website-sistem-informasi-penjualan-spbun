<?php

namespace App\Models;

use App\Enums\ProductPriceHistoryTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'old_price',
        'new_price',
        'old_cost_price',
        'new_cost_price',
        'type'
    ];

    protected $casts = [
        'type' => ProductPriceHistoryTypeEnum::class,
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'old_cost_price' => 'decimal:2',
        'new_cost_price' => 'decimal:2',
    ];

    // --- Relationships ---
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
