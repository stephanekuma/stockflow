<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use App\Notifications\ExpiredProductsNotification;
use Illuminate\Console\Command;

class SendExpiryAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:send-expiry {--store= : Store ID to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send expiry alerts to users for perishable products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for perishable products and sending alerts...');

        $storeId = $this->option('store');

        // Récupérer les produits expirés et expirant bientôt
        $expiredProducts = Product::expired();
        $expiringSoonProducts = Product::expiringSoon();

        if ($storeId) {
            $expiredProducts = $expiredProducts->where('store_id', $storeId);
            $expiringSoonProducts = $expiringSoonProducts->where('store_id', $storeId);
        }

        $expiredProducts = $expiredProducts->get();
        $expiringSoonProducts = $expiringSoonProducts->get();

        if ($expiredProducts->isEmpty() && $expiringSoonProducts->isEmpty()) {
            $this->info('No products requiring alerts found.');
            return Command::SUCCESS;
        }

        // Récupérer tous les utilisateurs
        $users = User::all();

        if ($users->isEmpty()) {
            $this->warn('No users found to send alerts to.');
            return Command::SUCCESS;
        }

        $notificationCount = 0;

        foreach ($users as $user) {
            try {
                $notification = new ExpiredProductsNotification($expiredProducts, $expiringSoonProducts);
                $user->notify($notification);
                $notificationCount++;

                $this->line("Alert sent to user: {$user->name} ({$user->email})");
            } catch (\Exception $e) {
                $this->error("Failed to send alert to user {$user->name}: " . $e->getMessage());
            }
        }

        $this->info("Sent {$notificationCount} alerts to users.");
        $this->info("Found {$expiredProducts->count()} expired products and {$expiringSoonProducts->count()} products expiring soon.");

        return Command::SUCCESS;
    }
}
