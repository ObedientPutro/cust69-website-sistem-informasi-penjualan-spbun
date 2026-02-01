<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_name',
        'address',
        'phone',
        'public_email',
        'logo_left',
        'logo_right',
        'notification_email',
        'enable_email_notification',
        'enable_web_notification',
    ];

    protected $casts = [
        'enable_email_notification' => 'boolean',
        'enable_web_notification' => 'boolean',
    ];

    protected $appends = ['logo_left_url', 'logo_right_url'];

    public function getLogoLeftUrlAttribute(): ?string
    {
        return $this->logo_left ? Storage::url($this->logo_left) : null;
    }

    public function getLogoRightUrlAttribute(): ?string
    {
        return $this->logo_right ? Storage::url($this->logo_right) : null;
    }
}
