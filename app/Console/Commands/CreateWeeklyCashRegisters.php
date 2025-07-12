<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\CashRegister;
use Carbon\Carbon;

class CreateWeeklyCashRegisters extends Command
{
    protected $signature = 'cash-registers:create-weekly';
    protected $description = 'Auto-create a cash register for each store at the start of each week if not already present.';

    public function handle()
    {
        $now = now();
        $week = $now->format('W');
        $year = $now->format('Y');
        $registerName = "Week {$week}, {$year}";

        $stores = Store::all();
        foreach ($stores as $store) {
            $exists = CashRegister::where('store_id', $store->id)
                ->where('name', $registerName)
                ->exists();
            if (!$exists) {
                $initialBalance = 0; // You can customize this logic if needed
                CashRegister::create([
                    'store_id' => $store->id,
                    'name' => $registerName,
                    'initial_balance' => $initialBalance,
                    'current_balance' => $initialBalance,
                ]);
                $this->info("Created cash register '{$registerName}' for store ID {$store->id}");
            } else {
                $this->line("Cash register '{$registerName}' already exists for store ID {$store->id}");
            }
        }
        $this->info('Weekly cash register creation complete.');
    }
}
