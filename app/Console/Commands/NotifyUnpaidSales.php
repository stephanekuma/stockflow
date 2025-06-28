<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

class NotifyUnpaidSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:unpaid-sales';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notifier les admins si une vente reste impayée après 7 jours (via Filament)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = now()->subDays(7);
        $unpaidSales = Sale::where('total', '>', 0)
            ->whereRaw('total > (select coalesce(sum(amount),0) from sale_payments where sale_id = sales.id)')
            ->where('sold_at', '<=', $threshold)
            ->get();

        $admins = User::where('email', 'like', '%@inventory.test%')->get();

        foreach ($unpaidSales as $sale) {
            foreach ($admins as $admin) {
                Notification::make()
                    ->title('Vente impayée depuis plus de 7 jours')
                    ->body("La vente #{$sale->invoice_number} du client {$sale->customer->name} (montant dû : {$sale->amount_due} XOF) n'est pas réglée depuis le " . $sale->sold_at->format('d/m/Y'))
                    ->persistent()
                    ->sendToDatabase($admin);
            }
        }

        $this->info(count($unpaidSales) . ' ventes impayées notifiées aux admins.');
    }
}
