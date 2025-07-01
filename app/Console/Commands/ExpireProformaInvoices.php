<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProformaInvoice;
use Carbon\Carbon;

class ExpireProformaInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proforma:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire proforma invoices that have passed their valid_until date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired proforma invoices...');

        $expiredInvoices = ProformaInvoice::where('valid_until', '<', Carbon::now())
            ->whereNotIn('status', [ProformaInvoice::STATUS_EXPIRED, ProformaInvoice::STATUS_CONVERTED])
            ->get();

        $count = 0;
        foreach ($expiredInvoices as $invoice) {
            $invoice->update(['status' => ProformaInvoice::STATUS_EXPIRED]);
            $count++;

            $this->line("Expired proforma invoice: {$invoice->invoice_number} (Customer: {$invoice->customer->name})");
        }

        if ($count > 0) {
            $this->info("Successfully expired {$count} proforma invoice(s).");
        } else {
            $this->info('No proforma invoices to expire.');
        }

        return Command::SUCCESS;
    }
}
