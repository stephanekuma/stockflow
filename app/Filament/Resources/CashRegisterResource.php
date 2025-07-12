<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashRegisterResource\Pages;
use App\Filament\Resources\CashRegisterResource\RelationManagers;
use App\Models\CashRegister;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashRegisterResource extends Resource
{
    protected static ?string $model = CashRegister::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Cash Management';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Hidden::make('store_id')
                        ->default(Filament::getTenant()->id),
                    Forms\Components\TextInput::make('name')
                        ->label(__('Name'))
                        ->required()
                        ->default(function () {
                            $now = now();
                            $dayName = $now->locale('fr')->dayName;
                            $date = $now->format('d/m/Y');
                            return "Caisse {$dayName} {$date}";
                        })
                        ->disabled()
                        ->dehydrated(true),
                    Forms\Components\TextInput::make('initial_balance')
                        ->label(__('Initial Balance'))
                        ->numeric()
                        ->required()
                        ->default(10000)
                        ->prefix('XOF')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('current_balance', $state);
                        }),
                    Forms\Components\TextInput::make('current_balance')
                        ->label(__('Current Balance'))
                        ->numeric()
                        ->required()
                        ->prefix('XOF')
                        ->disabled()
                        ->dehydrated(true),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('store.name')
                ->label(__('Store'))
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('name')
                ->label(__('Name'))
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('initial_balance')
                ->label(__('Initial Balance'))
                ->money('XOF')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('current_balance')
                ->label(__('Current Balance'))
                ->money('XOF')
                ->sortable()
                ->searchable(),
            Tables\Columns\IconColumn::make('is_closed')
                ->label(__('Status'))
                ->boolean()
                ->trueIcon('heroicon-o-lock-closed')
                ->falseIcon('heroicon-o-lock-open')
                ->trueColor('danger')
                ->falseColor('success')
                ->getStateUsing(fn($record) => $record->is_closed)
                ->label(fn($record) => $record->is_closed ? 'Fermée' : 'Ouverte'),
            Tables\Columns\TextColumn::make('created_at')
                ->label(__('Created At'))
                ->dateTime()
                ->sortable()
                ->searchable(),
        ])->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('open')
                    ->label('Ouvrir')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn($record) => $record->is_closed)
                    ->action(function ($record) {
                        $record->open();
                        \Filament\Notifications\Notification::make()
                            ->title('Caisse ouverte')
                            ->success()
                            ->body("La caisse '{$record->name}' a été ouverte.")
                            ->send();
                    }),
                Tables\Actions\Action::make('close')
                    ->label('Fermer')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn($record) => !$record->is_closed)
                    ->action(function ($record) {
                        $record->close();
                        \Filament\Notifications\Notification::make()
                            ->title('Caisse fermée')
                            ->success()
                            ->body("La caisse '{$record->name}' a été fermée.")
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashRegisters::route('/'),
            'create' => Pages\CreateCashRegister::route('/create'),
            'edit' => Pages\EditCashRegister::route('/{record}/edit'),
            'view' => Pages\ViewCashRegister::route('/{record}'),
        ];
    }
}
