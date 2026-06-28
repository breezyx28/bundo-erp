<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A single, data-driven notification used for every in-app alert. The channels
 * are resolved per user from their saved preferences (database is always on;
 * email is opt-in), so one class serves the whole notification matrix.
 */
class SystemAlert extends Notification
{
    use Queueable;

    public const LEVEL_ALERT = 'alert';

    public const LEVEL_REMINDER = 'reminder';

    public const LEVEL_INFO = 'info';

    public const LEVEL_SUCCESS = 'success';

    public function __construct(
        public string $level,
        public string $title,
        public string $message,
        public ?string $url = null,
        public string $icon = 'o-bell',
        public ?int $branchId = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $prefs = data_get($notifiable, 'settings.notifications', []);
        if (is_array($prefs) && ($prefs['mail'] ?? false) === true && ! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting($this->title)
            ->line($this->message);

        if ($this->url !== null) {
            $mail->action(__('notifications.view'), url($this->url));
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'level' => $this->level,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->icon,
            'branch_id' => $this->branchId,
        ];
    }
}
