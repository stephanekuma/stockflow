<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class StockHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'stockHistories';
    protected static ?string $title = 'Mouvements de stock';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime()->sortable(),
                TextColumn::make('productUnit.unit.name')->label('Unité'),
                TextColumn::make('quantity_before')->label('Avant'),
                TextColumn::make('quantity_change')->label('Mouvement')->color(fn($state) => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('quantity_after')->label('Après'),
                BadgeColumn::make('type')->label('Type')->colors([
                    'success' => 'achat',
                    'danger' => 'vente',
                    'warning' => 'correction',
                ]),
                TextColumn::make('provider.name')
                    ->label('Provider')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('note')->label('Note')->limit(30),
                TextColumn::make('user.name')->label('Utilisateur'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'achat' => 'Achat',
                        'vente' => 'Vente',
                        'vente (pack)' => 'Vente (Pack)',
                        'correction' => 'Correction',
                    ]),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
