<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;

class ProductUnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'units';
    protected static ?string $title = 'Units';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('store_id')
                ->default(Filament::getTenant()->id),
            Forms\Components\Select::make('unit_id')
                ->native(false)
                ->preload()
                ->searchable()
                ->label(__('Unit'))
                ->relationship('unit', 'name')
                ->required(),
            Forms\Components\TextInput::make('quantity')
                ->label(__('Quantity'))
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('cost_price')
                ->label(__('Cost Price'))
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('price')
                ->label(__('Price'))
                ->numeric()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('unit.name')->label(__('Unit')),
            Tables\Columns\TextColumn::make('quantity')->label(__('Quantity')),
            Tables\Columns\TextColumn::make('cost_price')->label(__('Cost Price')),
            Tables\Columns\TextColumn::make('price')->label(__('Price')),
        ])->headerActions([
            Tables\Actions\CreateAction::make(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }
}
