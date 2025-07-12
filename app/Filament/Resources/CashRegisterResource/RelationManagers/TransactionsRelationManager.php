<?php

namespace App\Filament\Resources\CashRegisterResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';
    protected static ?string $title = 'Transactions';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->options([
                    'sale' => 'Sale',
                    'deposit' => 'Deposit',
                    'inter-transfer-in' => 'Inter-Transfer In',
                    'inter-transfer-out' => 'Inter-Transfer Out',
                    'return-as-deposit' => 'Return as Deposit',
                    'opening' => 'Opening',
                    'closing' => 'Closing',
                ])
                ->required(),
            Forms\Components\TextInput::make('amount')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('description'),
            Forms\Components\Select::make('created_by')
                ->relationship('creator', 'name')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('type')->badge(),
            Tables\Columns\TextColumn::make('amount')->money('XOF'),
            Tables\Columns\TextColumn::make('description')->limit(30),
            Tables\Columns\TextColumn::make('creator.name')->label('Created By'),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ])->headerActions([
            Tables\Actions\CreateAction::make(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }
}
