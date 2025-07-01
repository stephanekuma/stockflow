<?php

namespace App\Filament\Widgets;

use App\Models\ProformaInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;

class PendingProformaInvoicesWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $storeId = Filament::getTenant()->id;

        $draftCount = ProformaInvoice::where('store_id', $storeId)
            ->where('status', ProformaInvoice::STATUS_DRAFT)
            ->count();

        $sentCount = ProformaInvoice::where('store_id', $storeId)
            ->where('status', ProformaInvoice::STATUS_SENT)
            ->count();

        $acceptedCount = ProformaInvoice::where('store_id', $storeId)
            ->where('status', ProformaInvoice::STATUS_ACCEPTED)
            ->count();

        $expiredCount = ProformaInvoice::where('store_id', $storeId)
            ->where('status', ProformaInvoice::STATUS_EXPIRED)
            ->count();

        return [
            Stat::make(__('Brouillons'), $draftCount)
                ->description(__('Factures proforma en brouillon'))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make(__('Envoyées'), $sentCount)
                ->description(__('Factures proforma envoyées'))
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('blue'),

            Stat::make(__('Acceptées'), $acceptedCount)
                ->description(__('Factures proforma acceptées'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('green'),

            Stat::make(__('Expirées'), $expiredCount)
                ->description(__('Factures proforma expirées'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('yellow'),
        ];
    }
}
