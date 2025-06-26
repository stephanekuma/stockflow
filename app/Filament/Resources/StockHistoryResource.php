<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockHistoryResource\Pages;
use App\Filament\Resources\StockHistoryResource\RelationManagers;
use App\Models\StockHistory;
use App\Models\ProductUnit;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\Pages\Page;

class StockHistoryResource extends Resource
{
    protected static ?string $model = StockHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?int $navigationSort = 99;

    public static function getNavigationGroup(): string
    {
        return __('Products Management');
    }

    public static function getModelLabel(): string
    {
        return __('Stock History');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stock Histories');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->label(__('Type'))
                    ->colors([
                        'danger' => fn($state) => $state === 'vente',
                        'warning' => fn($state) => $state === 'vente (pack)',
                        'success' => fn($state) => $state === 'achat',
                        'gray' => fn($state) => !in_array($state, ['vente', 'vente (pack)', 'achat']),
                    ])
                    ->icons([
                        'heroicon-o-arrow-trending-down' => 'vente',
                        'heroicon-o-cube' => 'vente (pack)',
                        'heroicon-o-arrow-trending-up' => 'achat',
                        'heroicon-o-question-mark-circle' => fn($state) => !in_array($state, ['vente', 'vente (pack)', 'achat']),
                    ]),
                TextColumn::make('productUnit.product.name')
                    ->label(__('Produit')),
                TextColumn::make('productUnit.unit.name')
                    ->label(__('Unité')),
                TextColumn::make('quantity_before')
                    ->label(__('Avant')),
                TextColumn::make('quantity_after')
                    ->label(__('Après')),
                TextColumn::make('quantity_change')
                    ->label(__('Changement')),
                TextColumn::make('user.name')
                    ->label(__('Utilisateur')),
                TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->since(),
                TextColumn::make('note')
                    ->label(__('Note')),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type de mouvement')
                    ->options([
                        'vente' => 'Vente',
                        'vente (pack)' => 'Vente (Pack)',
                        'achat' => 'Achat',
                        // Ajoute d'autres types si besoin
                    ]),
                SelectFilter::make('product_unit_id')
                    ->label('Unité de produit')
                    ->relationship('productUnit', 'id')
                    ->getOptionLabelFromRecordUsing(fn($record) => ($record->product->name ?? '') . ' (' . ($record->unit->name ?? '') . ')'),
                SelectFilter::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name'),
                Filter::make('created_at')
                    ->label('Date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('Du'),
                        Forms\Components\DatePicker::make('created_until')->label('Au'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                ViewAction::make(),
                DeleteAction::make(),
                Action::make('export_csv')
                    ->label('Exporter CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($record) {
                        // Export d'une seule ligne (optionnel)
                        // Peut être adapté pour exporter la table entière via bulk
                    })
                    ->visible(false), // Laisse l'export en bulk
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportAction::make()
                        ->label('Exporter la sélection')
                        ->icon('heroicon-o-arrow-down-tray'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockHistories::route('/'),
            // 'create' => Pages\CreateStockHistory::route('/create'),
            // 'view' => Pages\ViewStockHistory::route('/{record}'),
            // 'edit' => Pages\EditStockHistory::route('/{record}/edit'),
            'timeline' => Pages\TimelineStockHistory::route('/timeline'),
        ];
    }
}
