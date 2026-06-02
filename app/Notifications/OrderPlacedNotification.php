<?php

namespace App\Notifications;

use App\Models\CombinedOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    
    protected $combined_order;

    public function __construct(CombinedOrder $combined_order)
    {
        $this->combined_order = $combined_order;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $array['subject'] = translate('Order has been placed') . ' - ' . $this->combined_order->code;
        $array['order'] = $this->combined_order;

        return (new MailMessage)
            ->view('emails.invoice', ['array' => $array, 'combined_order' => $this->combined_order])
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject(translate('Order Placed').' - '.config('app.name'));
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
