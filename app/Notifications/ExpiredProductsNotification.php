<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class ExpiredProductsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $expiredProducts;
    protected $expiringSoonProducts;

    /**
     * Create a new notification instance.
     */
    public function __construct($expiredProducts = [], $expiringSoonProducts = [])
    {
        $this->expiredProducts = $expiredProducts;
        $this->expiringSoonProducts = $expiringSoonProducts;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Alerte Produits Périssables')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Nous avons détecté des produits périssables qui nécessitent votre attention.');

        if (count($this->expiredProducts) > 0) {
            $message->line('**Produits expirés :** ' . count($this->expiredProducts));
            foreach ($this->expiredProducts->take(3) as $product) {
                $message->line('- ' . $product->name . ' (Expiré le ' . $product->expiry_date->format('d/m/Y') . ')');
            }
        }

        if (count($this->expiringSoonProducts) > 0) {
            $message->line('**Produits expirant bientôt :** ' . count($this->expiringSoonProducts));
            foreach ($this->expiringSoonProducts->take(3) as $product) {
                $message->line('- ' . $product->name . ' (Expire le ' . $product->expiry_date->format('d/m/Y') . ')');
            }
        }

        $message->action('Voir les détails', url('/store/products'))
            ->line('Merci d\'utiliser notre application !');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Alerte Produits Périssables',
            'message' => $this->getNotificationMessage(),
            'expired_count' => count($this->expiredProducts),
            'expiring_soon_count' => count($this->expiringSoonProducts),
            'type' => 'expiry_alert',
        ];
    }

    /**
     * Get the notification message for Filament
     */
    public function getNotificationMessage(): string
    {
        $message = '';

        if (count($this->expiredProducts) > 0) {
            $message .= count($this->expiredProducts) . ' produit(s) expiré(s). ';
        }

        if (count($this->expiringSoonProducts) > 0) {
            $message .= count($this->expiringSoonProducts) . ' produit(s) expire(nt) bientôt.';
        }

        return $message;
    }

    /**
     * Send Filament notification
     */
    public function sendFilamentNotification($user)
    {
        $message = $this->getNotificationMessage();

        FilamentNotification::make()
            ->title('Alerte Produits Périssables')
            ->body($message)
            ->warning()
            ->persistent()
            ->sendToDatabase($user);
    }
}
