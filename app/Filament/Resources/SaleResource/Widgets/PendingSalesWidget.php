<?php

namespace App\Filament\Resources\SaleResource\Widgets;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;

class PendingSalesWidget extends BaseWidget
{
    protected static ?string $heading = 'Ventes en attente';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Sale::query()
                    ->where('status', Sale::STATUS_PENDING)
                    ->where('store_id', Filament::getTenant()->id)
                    ->with(['customer', 'soldProducts'])
                    ->latest('sold_at')
            )
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Facture')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('sold_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('XOF')
                    ->sortable(),
                TextColumn::make('soldProducts_count')
                    ->label('Produits')
                    ->counts('soldProducts'),
            ])
            ->actions([
                Action::make('resume')
                    ->label('Reprendre')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(function (Sale $record) {
                        // Rediriger vers la page de création avec les données de la vente
                        return redirect()->route('filament.admin.resources.sales.create', [
                            'tenant' => Filament::getTenant(),
                            'resume_sale_id' => $record->id
                        ]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reprendre cette vente')
                    ->modalDescription('Cette vente sera reprise avec toutes ses données.')
                    ->modalSubmitActionLabel('Reprendre'),
            ])
            ->paginated(false)
            ->defaultSort('sold_at', 'desc')
            ->emptyStateHeading('Aucune vente en attente')
            ->emptyStateDescription('Toutes les ventes sont en cours ou terminées.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
