<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\ProductUnit;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ProductUnit $productUnit;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProductUnit $productUnit)
    {
        $this->productUnit = $productUnit;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $product = $this->productUnit->product->name ?? 'Produit inconnu';
        $unit = $this->productUnit->unit->name ?? '';
        $quantity = $this->productUnit->quantity;
        $threshold = $this->productUnit->low_stock_threshold;
        return (new MailMessage)
            ->subject('Alerte stock bas')
            ->greeting('Bonjour,')
            ->line("Le stock du produit : {$product} (Unité : {$unit}) est passé sous le seuil d'alerte.")
            ->line("Stock restant : {$quantity} | Seuil d'alerte : {$threshold}")
            ->action('Voir le produit', url('/admin/products'))
            ->line('Merci de réapprovisionner ce produit rapidement.');
    }

    /**
     * Get the array representation of the notification for database.
     */
    public function toDatabase($notifiable)
    {
        $product = $this->productUnit->product->name ?? 'Produit inconnu';
        $unit = $this->productUnit->unit->name ?? '';
        $quantity = $this->productUnit->quantity;
        $threshold = $this->productUnit->low_stock_threshold;
        return [
            'title' => 'Alerte stock bas',
            'body' => "Le stock du produit : {$product} (Unité : {$unit}) est passé sous le seuil d'alerte. Stock restant : {$quantity} | Seuil d'alerte : {$threshold}",
            'product_unit_id' => $this->productUnit->id,
            'url' => url('/admin/products'),
        ];
    }
}
