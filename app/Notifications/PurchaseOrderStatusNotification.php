<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public string $event
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = match ($this->event) {
            'created' => 'A purchase order is waiting for approval.',
            'approved' => 'A purchase order has been approved.',
            'rejected' => 'A purchase order was rejected.',
            'ordered' => 'A purchase order has been placed with the supplier.',
            'received' => 'A purchase order delivery has been recorded.',
            default => 'A purchase order status has changed.',
        };

        return (new MailMessage)
            ->subject('Purchase order '.$this->purchaseOrder->order_number)
            ->line($message)
            ->line('Supplier: '.$this->purchaseOrder->supplier?->name)
            ->action('View purchase order', url('/purchase-orders/'.$this->purchaseOrder->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'purchase_order_id' => $this->purchaseOrder->id,
            'order_number' => $this->purchaseOrder->order_number,
            'status' => $this->purchaseOrder->status,
            'event' => $this->event,
            'message' => 'Purchase order '.$this->purchaseOrder->order_number.': '.$this->event.'.',
        ];
    }
}
