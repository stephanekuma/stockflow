<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackResource\Pages;
use App\Filament\Resources\PackResource\RelationManagers;
use App\Models\Pack;
use App\Models\ProductUnit;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackResource extends Resource
{
    protected static ?string $model = Pack::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): string
    {
        return __('Products Management');
    }

    public static function getModelLabel(): string
    {
        return __('Pack');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Packs');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make(__('Pack Information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('price')
                            ->label(__('Price'))
                            ->required()
                            ->numeric()
                            ->prefix('XOF')
                            ->readOnly()
                            ->reactive(),
                        Forms\Components\KeyValue::make('data')
                            ->label(__('Extra Details'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(__('Product Information'))
                    ->schema([
                        Forms\Components\Repeater::make('packProducts')
                            ->label(__('Product\'s Pack'))
                            ->addActionLabel(__('Add Product To Pack'))
                            ->collapsible()
                            ->relationship()
                            ->schema([
                                Forms\Components\Hidden::make('store_id')
                                    ->default(Filament::getTenant()->id),
                                Forms\Components\Select::make('product_id')
                                    ->label(__('Product'))
                                    ->options(fn() => \App\Models\Product::query()
                                        ->where('store_id', Filament::getTenant()->id)
                                        ->pluck('name', 'id'))
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn($state, Forms\Get $get, Forms\Set $set) => $set('product_unit_id', null)),
                                Forms\Components\Select::make('product_unit_id')
                                    ->label(__('Unit'))
                                    ->options(function (Forms\Get $get) {
                                        $productId = $get('product_id');
                                        if (!$productId) {
                                            return [];
                                        }
                                        return \App\Models\ProductUnit::query()
                                            ->where('product_id', $productId)
                                            ->with('unit')
                                            ->get()
                                            ->mapWithKeys(function ($productUnit) {
                                                $label = "{$productUnit->unit->name} ({$productUnit->unit->key} - {$productUnit->price} XOF)";
                                                return [$productUnit->id => $label];
                                            });
                                    })
                                    ->required()
                                    ->reactive()
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Forms\Components\TextInput::make('quantity')
                                    ->label(__('Quantity'))
                                    ->live()
                                    ->required()
                                    ->numeric()
                                    ->afterStateUpdated(fn(Forms\Get $get, Forms\Set $set) => self::updatePackPrice($get, $set)),
                            ])
                            ->columns(2)
                            ->cloneable()
                            ->reactive()
                            ->afterStateUpdated(fn(Forms\Get $get, Forms\Set $set) => self::updatePackPrice($get, $set))
                            ->afterStateHydrated(fn(Forms\Get $get, Forms\Set $set) => self::updatePackPrice($get, $set)),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('store_id')
                //     ->numeric()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label(__('Price'))
                    ->money(currency: 'XOF', locale: 'fr')
                    ->sortable(),
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
            'index' => Pages\ListPacks::route('/'),
            'create' => Pages\CreatePack::route('/create'),
            'edit' => Pages\EditPack::route('/{record}/edit'),
        ];
    }

    public static function updatePackPrice(Get $get, Set $set): void
    {
        $items = $get('packProducts') ?? [];

        $total = 0.0;

        foreach ($items as $item) {
            $unitId = $item['product_unit_id'] ?? null;
            $qty = (int) $item['quantity'] ?? 1;

            if ($unitId) {
                $price = ProductUnit::find($unitId)?->price ?? 0;
                $total += (float) $price * $qty;
            }
        }

        $set('price', $total);
    }
}
