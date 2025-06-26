<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\SalePayment;

class DepositsRelationManager extends RelationManager
{
    protected static string $relationship = 'deposits';
    protected static ?string $title = 'Dépôts';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('amount')
                ->label('Montant')
                ->numeric()
                ->required(),
            Forms\Components\Textarea::make('note')
                ->label('Note'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('amount')->label('Montant')->money('XOF'),
            Tables\Columns\TextColumn::make('note')->label('Note'),
            Tables\Columns\TextColumn::make('user.name')->label('Utilisateur'),
            Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime(),
        ])->headerActions([
            Tables\Actions\CreateAction::make(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }
}

class SalePaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'salePayments';
    protected static ?string $title = 'Paiements ventes';

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('sale.invoice_number')->label('Vente'),
            Tables\Columns\TextColumn::make('amount')->label('Montant')->money('XOF'),
            Tables\Columns\TextColumn::make('user.name')->label('Utilisateur'),
            Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime(),
            Tables\Columns\TextColumn::make('note')->label('Note'),
        ]);
    }
}
