<?php

namespace App\Models;

use App\Enums\ShipTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'manager_name',
        'owner_name',
        'ship_name',
        'ship_type',
        'gross_tonnage',
        'pk_engine',
        'phone',
        'address',
        'photo',
        'credit_limit',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'gross_tonnage' => 'decimal:2',
        'pk_engine' => 'decimal:2',
        'ship_type' => ShipTypeEnum::class,
    ];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo
            ? Storage::url($this->photo)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->owner_name) . '&color=F97316&background=FFF7ED';
    }

    public function getLabelAttribute(): string
    {
        return "{$this->ship_name} ({$this->manager_name})";
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
