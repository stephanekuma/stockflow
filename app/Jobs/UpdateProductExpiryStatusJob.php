<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateProductExpiryStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting product expiry status update job');

        $products = Product::perishable()->get();
        $updatedCount = 0;

        foreach ($products as $product) {
            $oldStatus = $product->expiry_status;
            $product->updateExpiryStatus();

            if ($oldStatus !== $product->expiry_status) {
                $updatedCount++;
                Log::info("Product '{$product->name}' expiry status updated: {$oldStatus} → {$product->expiry_status}");
            }
        }

        Log::info("Product expiry status update completed. Updated {$updatedCount} products out of {$products->count()} perishable products.");
    }
}
