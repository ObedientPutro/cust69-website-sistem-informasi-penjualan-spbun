<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\AnonymousNotifiable;

class SystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $title;
    public $message;
    public $url;
    public $type;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $url = null, $type = 'info')
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[SPBU-N Alert] ' . $this->title)
            ->greeting('Halo, Owner!')
            ->line($this->message)
            ->action('Buka Aplikasi', $this->url ?? url('/'))
            ->line('Terima kasih telah menggunakan sistem kami.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'type' => $this->type,
        ];
    }
}
