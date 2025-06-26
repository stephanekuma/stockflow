<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SalePaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $title = 'Paiements';

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
            Tables\Columns\TextColumn::make('user.name')->label('Utilisateur'),
            Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime(),
            Tables\Columns\TextColumn::make('note')->label('Note'),
        ])->headerActions([
            Tables\Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data) {
                    $data['user_id'] = Auth::id();
                    return $data;
                }),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }
}
