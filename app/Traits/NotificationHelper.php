<?php

namespace App\Traits;

use App\Enums\UserRoleEnum;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Notification;

class NotificationHelper
{
    public static function send(string $title, string $message, string $url = null, string $type = 'info'): void
    {
        $settings = SiteSetting::first();
        if (!$settings) return;

        if ($settings->enable_web_notification) {
            $owners = User::where('role', UserRoleEnum::OWNER)->get();
            Notification::send($owners, new SystemNotification($title, $message, $url, $type));
        }

        if ($settings->enable_email_notification && $settings->notification_email) {
            Notification::route('mail', $settings->notification_email)
                ->notify(new SystemNotification($title, $message, $url, $type));
        }
    }
}
