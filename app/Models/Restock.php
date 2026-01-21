<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Restock extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'date',
        'volume_liter',
        'total_cost',
        'unit_cost',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
        'volume_liter' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
