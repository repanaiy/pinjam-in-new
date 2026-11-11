<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Notifikasi in-app (Laravel Database Notifications). */
class LoanNotification extends Notification
{
    /** @param array{kind:string,title:string,message:string,loan_id:?int} $payload */
    public function __construct(public array $payload) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }
}
