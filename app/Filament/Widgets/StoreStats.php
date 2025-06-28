<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use App\Models\Sale;
use App\Models\SoldProduct;
use App\Models\ProductUnit;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerDeposit;
use App\Models\SalePayment;
use App\Models\Provider;
use App\Models\Purchase;
use Illuminate\Support\Carbon;

class StoreStats extends BaseWidget
{
    protected function getStats(): array
    {
        $storeId = Filament::getTenant()->id;
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $week = now()->startOfWeek();
        $month = now()->startOfMonth();

        // Ventes
        $salesCount = Sale::where('store_id', $storeId)->count();
        $salesToday = Sale::where('store_id', $storeId)->whereDate('created_at', $today)->count();
        $salesYesterday = Sale::where('store_id', $storeId)->whereDate('created_at', $yesterday)->count();
        $salesWeek = Sale::where('store_id', $storeId)->whereBetween('created_at', [$week, now()])->count();
        $salesMonth = Sale::where('store_id', $storeId)->whereBetween('created_at', [$month, now()])->count();
        $caTotal = Sale::where('store_id', $storeId)->sum('total');
        $caToday = Sale::where('store_id', $storeId)->whereDate('created_at', $today)->sum('total');
        $caYesterday = Sale::where('store_id', $storeId)->whereDate('created_at', $yesterday)->sum('total');
        $caWeek = Sale::where('store_id', $storeId)->whereBetween('created_at', [$week, now()])->sum('total');
        $caMonth = Sale::where('store_id', $storeId)->whereBetween('created_at', [$month, now()])->sum('total');
        $ticketMoyen = $salesCount > 0 ? $caTotal / $salesCount : 0;

        // Stock
        $productsCount = Product::where('store_id', $storeId)->count();
        $stockCount = ProductUnit::where('store_id', $storeId)->sum('quantity');
        $ruptures = ProductUnit::where('store_id', $storeId)
            ->where(function ($q) {
                $q->where('quantity', '<=', 0)
                    ->orWhereRaw('quantity <= low_stock_threshold');
            })->count();
        $valeurStock = ProductUnit::where('store_id', $storeId)
            ->selectRaw('SUM(quantity * cost_price) as total')->value('total') ?? 0;

        // Clients
        $clientsCount = Customer::where('store_id', $storeId)->count();
        $nouveauxClientsMois = Customer::where('store_id', $storeId)->whereBetween('created_at', [$month, now()])->count();
        $soldeTotalClients = Customer::where('store_id', $storeId)->get()->sum('balance');
        $clientsCredit = Customer::where('store_id', $storeId)->get()->filter(fn($c) => $c->balance < 0)->count();

        // Fournisseurs/Achats
        $providersCount = Provider::where('store_id', $storeId)->count();
        $achatsTotal = Purchase::where('store_id', $storeId)->sum('total');
        $achatsToday = Purchase::where('store_id', $storeId)->whereDate('created_at', $today)->sum('total');
        $achatsYesterday = Purchase::where('store_id', $storeId)->whereDate('created_at', $yesterday)->sum('total');
        $achatsWeek = Purchase::where('store_id', $storeId)->whereBetween('created_at', [$week, now()])->sum('total');
        $achatsMonth = Purchase::where('store_id', $storeId)->whereBetween('created_at', [$month, now()])->sum('total');
        $achatsCount = Purchase::where('store_id', $storeId)->count();
        $achatsCountToday = Purchase::where('store_id', $storeId)->whereDate('created_at', $today)->count();
        $achatsCountWeek = Purchase::where('store_id', $storeId)->whereBetween('created_at', [$week, now()])->count();
        $achatsCountMonth = Purchase::where('store_id', $storeId)->whereBetween('created_at', [$month, now()])->count();

        // Financier
        $depotClients = CustomerDeposit::where('store_id', $storeId)->sum('amount');
        $paiementsRecus = SalePayment::where('store_id', $storeId)->sum('amount');
        $creditsClients = Customer::where('store_id', $storeId)->get()->sum(function ($c) {
            return $c->balance < 0 ? abs($c->balance) : 0;
        });

        // Personnalisé
        $produitPlusVendu = SoldProduct::whereHas('sale', fn($q) => $q->where('store_id', $storeId))
            ->selectRaw('product_unit_id, SUM(quantity) as total')
            ->groupBy('product_unit_id')
            ->orderByDesc('total')
            ->first();
        $produitPlusVenduLabel = $produitPlusVendu ?
            optional($produitPlusVendu->productUnit->product)->name . ' (' . $produitPlusVendu->total . ')' : 'N/A';

        return [
            Stat::make('Ventes totales', $salesCount)
                ->icon('heroicon-o-shopping-cart')
                ->color('primary')
                ->description('Toutes périodes'),
            Stat::make('Ventes aujourd\'hui', $salesToday)
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->description('Aujourd\'hui')
                ->descriptionIcon($salesToday > $salesYesterday ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->descriptionColor($salesToday > $salesYesterday ? 'success' : 'danger'),
            Stat::make('Ventes cette semaine', $salesWeek)
                ->icon('heroicon-o-calendar')
                ->color('info')
                ->description('Depuis lundi'),
            Stat::make('Ventes ce mois', $salesMonth)
                ->icon('heroicon-o-calendar')
                ->color('info')
                ->description('Depuis le 1er'),
            Stat::make('Chiffre d\'affaires total', number_format($caTotal, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-currency-dollar')
                ->color('primary')
                ->description('Toutes ventes'),
            Stat::make('CA aujourd\'hui', number_format($caToday, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->description('Aujourd\'hui')
                ->descriptionIcon($caToday > $caYesterday ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->descriptionColor($caToday > $caYesterday ? 'success' : 'danger'),
            Stat::make('CA cette semaine', number_format($caWeek, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-currency-dollar')
                ->color('info')
                ->description('Depuis lundi'),
            Stat::make('CA ce mois', number_format($caMonth, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-currency-dollar')
                ->color('info')
                ->description('Depuis le 1er'),
            Stat::make('Ticket moyen', number_format($ticketMoyen, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-receipt-percent')
                ->color('gray')
                ->description('CA / ventes'),
            Stat::make('Produits référencés', $productsCount)
                ->icon('heroicon-o-archive-box')
                ->color('primary')
                ->description('Références distinctes'),
            Stat::make('Stock total', $stockCount)
                ->icon('heroicon-o-cube')
                ->color('info')
                ->description('Unités en stock'),
            Stat::make('Produits en rupture', $ruptures)
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->description('Stock sous seuil ou épuisé'),
            Stat::make('Valeur du stock', number_format($valeurStock, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->description('Coût d\'achat total'),
            Stat::make('Clients', $clientsCount)
                ->icon('heroicon-o-user-group')
                ->color('info')
                ->description('Tous clients'),
            Stat::make('Nouveaux clients ce mois', $nouveauxClientsMois)
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->description('Depuis le 1er'),
            Stat::make('Solde total clients', number_format($soldeTotalClients, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-wallet')
                ->color('primary')
                ->description('Dépôts - paiements'),
            Stat::make('Clients en crédit', $clientsCredit)
                ->icon('heroicon-o-exclamation-circle')
                ->color('danger')
                ->description('Solde négatif'),
            Stat::make('Fournisseurs', $providersCount)
                ->icon('heroicon-o-truck')
                ->color('gray')
                ->description('Tous fournisseurs'),
            Stat::make('Achats totaux', number_format($achatsTotal, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-shopping-bag')
                ->color('primary')
                ->description('Total achats'),
            Stat::make('Achats aujourd\'hui', number_format($achatsToday, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-shopping-bag')
                ->color('success')
                ->description('Achats du jour')
                ->descriptionIcon($achatsToday > $achatsYesterday ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->descriptionColor($achatsToday > $achatsYesterday ? 'success' : 'danger'),
            Stat::make('Achats cette semaine', number_format($achatsWeek, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-shopping-bag')
                ->color('info')
                ->description('Depuis lundi'),
            Stat::make('Achats ce mois', number_format($achatsMonth, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-shopping-bag')
                ->color('info')
                ->description('Depuis le 1er'),
            Stat::make('Nombre d\'achats', $achatsCount)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary')
                ->description('Tous achats'),
            Stat::make('Achats aujourd\'hui (nb)', $achatsCountToday)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('success')
                ->description('Nb achats du jour'),
            Stat::make('Achats cette semaine (nb)', $achatsCountWeek)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('info')
                ->description('Nb achats semaine'),
            Stat::make('Achats ce mois (nb)', $achatsCountMonth)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('info')
                ->description('Nb achats mois'),
            Stat::make('Dépôts clients', number_format($depotClients, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->description('Total dépôts'),
            Stat::make('Paiements reçus', number_format($paiementsRecus, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->description('Total paiements'),
            Stat::make('Crédits clients', number_format($creditsClients, 0, ',', ' ') . ' XOF')
                ->icon('heroicon-o-exclamation-circle')
                ->color('danger')
                ->description('Total crédits'),
            Stat::make('Produit le plus vendu', $produitPlusVenduLabel)
                ->icon('heroicon-o-fire')
                ->color('warning')
                ->description('Top ventes'),
        ];
    }
}
