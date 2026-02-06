<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TankSounding extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'recorded_at',
        'physical_height_cm',
        'physical_liter',
        'system_liter_snapshot',
        'difference',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'physical_height_cm' => 'float',
        'physical_liter' => 'float',
        'system_liter_snapshot' => 'float',
        'difference' => 'float',
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
