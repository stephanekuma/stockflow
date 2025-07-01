<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Filament\Resources\UnitResource\RelationManagers;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string
    {
        return __('Products Management');
    }

    public static function getModelLabel(): string
    {
        return __('Unit');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Units');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                self::getFormSchema(),
            );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('key')
                    ->label(__('Short Unit'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_base_unit')
                    ->label(__('Base Unit'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('conversion_factor')
                    ->label(__('Conversion Factor'))
                    ->numeric(
                        decimalPlaces: 4,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->sortable()
                    ->visible(fn($record) => !$record?->is_base_unit),
                Tables\Columns\TextColumn::make('baseUnit.name')
                    ->label(__('Base Unit'))
                    ->sortable()
                    ->visible(fn($record) => !$record?->is_base_unit),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('Name'))
                        ->required()
                        ->maxLength(255)
                        ->helperText(__('Kilograms, Gallons, Liters, etc')),
                    Forms\Components\TextInput::make('key')
                        ->label(__('Key'))
                        ->required()
                        ->maxLength(255)
                        ->helperText(__('Units in short form kg, gal, l, etc')),
                    Forms\Components\KeyValue::make('data')
                        ->label(__('Extra Details'))
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_base_unit')
                        ->label(__('Is Base Unit'))
                        ->helperText(__('Check if this is a base unit (e.g., piece, gram, milliliter)'))
                        ->reactive(),
                    Forms\Components\Select::make('base_unit_id')
                        ->label(__('Base Unit'))
                        ->relationship('baseUnit', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn(Forms\Get $get) => !$get('is_base_unit'))
                        ->required(fn(Forms\Get $get) => !$get('is_base_unit'))
                        ->helperText(__('Select the base unit for conversion (e.g., if this is "Carton", select "Piece" as base)')),
                    Forms\Components\TextInput::make('conversion_factor')
                        ->label(__('Conversion Factor'))
                        ->numeric()
                        ->step(1)
                        // ->minValue(0.0001)
                        ->visible(fn(Forms\Get $get) => !$get('is_base_unit'))
                        ->required(fn(Forms\Get $get) => !$get('is_base_unit'))
                        ->helperText(__('How many base units equal one of this unit? (e.g., 20 pieces = 1 carton)'))
                        ->default(1),
                ])->columns(2),
        ];
    }
}
