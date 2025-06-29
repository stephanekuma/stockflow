<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class UpdateProductExpiryStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-expiry-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update expiry status for all perishable products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating product expiry status...');

        $products = Product::perishable()->get();
        $updatedCount = 0;

        foreach ($products as $product) {
            $oldStatus = $product->expiry_status;
            $product->updateExpiryStatus();

            if ($oldStatus !== $product->expiry_status) {
                $updatedCount++;
                $this->line("Product '{$product->name}' status updated: {$oldStatus} → {$product->expiry_status}");
            }
        }

        $this->info("Updated {$updatedCount} products out of {$products->count()} perishable products.");

        return Command::SUCCESS;
    }
}
