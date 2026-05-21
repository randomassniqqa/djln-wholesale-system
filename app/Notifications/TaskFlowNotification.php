<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TaskFlowNotification extends Notification
{
    /**
     * ✅ UNIVERSAL NOTIFICATION BASE: Generic TaskFlow notification class
     * Used for all in-app alerts: assignments, completions, milestones
     */
    protected $title;
    protected $message;
    protected $actionUrl;
    protected $actionLabel;

    public function __construct($title, $message, $actionUrl = null, $actionLabel = 'View')
    {
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->actionLabel = $actionLabel;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
        ];
    }
}
