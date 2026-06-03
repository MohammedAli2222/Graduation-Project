<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SendOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;


    public function __construct(protected string $otpCode) {}


    public function via($notifiable): array
    {
        return ['mail'];
    }


    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تفعيل حسابك - رمز التحقق OTP')
            ->greeting('مرحباً بك في تطبيق العيادات!')
            ->line('🔑 **' . $this->otpCode . '**')
            ->line('ملاحظة: هذا الرمز صالح لمدة 5 دقائق فقط.');
    }
}
