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
                ->required()
                ->reactive(),
            Forms\Components\TextInput::make('quantity')
                ->label(__('Quantity'))
                ->numeric()
                ->required()
                ->minValue(0)
                ->step(1)
                ->default(0),
            Forms\Components\TextInput::make('low_stock_threshold')
                ->label(__('Seuil d\'alerte'))
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->helperText('Notifier si le stock passe sous ce seuil.'),
            Forms\Components\TextInput::make('cost_price')
                ->label(__('Cost Price'))
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('price')
                ->label(__('Price'))
                ->numeric()
                ->required(),

            // Section pour les conversions personnalisées
            Forms\Components\Section::make(__('Custom Unit Conversion'))
                ->description(__('Override default unit conversion for this specific product'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Toggle::make('use_custom_conversion')
                        ->label(__('Use Custom Conversion'))
                        ->helperText(__('Check to override the default unit conversion'))
                        ->reactive(),

                    Forms\Components\Select::make('custom_base_unit_id')
                        ->label(__('Custom Base Unit'))
                        ->relationship('customBaseUnit', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn(Forms\Get $get) => $get('use_custom_conversion'))
                        ->required(fn(Forms\Get $get) => $get('use_custom_conversion'))
                        ->helperText(__('Select a different base unit for this product')),

                    Forms\Components\TextInput::make('custom_conversion_factor')
                        ->label(__('Custom Conversion Factor'))
                        ->numeric()
                        ->step(0.0001)
                        ->minValue(0.0001)
                        ->visible(fn(Forms\Get $get) => $get('use_custom_conversion'))
                        ->required(fn(Forms\Get $get) => $get('use_custom_conversion'))
                        ->helperText(__('How many base units equal one of this unit for this product?')),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('unit.name')->label(__('Unit')),
            Tables\Columns\TextColumn::make('quantity')->label(__('Quantity')),
            Tables\Columns\TextColumn::make('cost_price')->label(__('Cost Price')),
            Tables\Columns\TextColumn::make('price')->label(__('Price')),
            Tables\Columns\IconColumn::make('custom_conversion_factor')
                ->label(__('Custom Conversion'))
                ->boolean()
                ->getStateUsing(fn($record) => $record->custom_conversion_factor !== null)
                ->tooltip(fn($record) => $record->custom_conversion_factor !== null
                    ? "Custom: {$record->custom_conversion_factor}"
                    : "Default: {$record->unit->conversion_factor}"),
        ])->headerActions([
            Tables\Actions\CreateAction::make(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }
}
