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
                            ->reactive()
                            ->afterStateHydrated(function (Forms\Get $get, Forms\Set $set) {
                                self::updatePackPrice($get, $set);
                            }),
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
                                Forms\Components\Select::make('product_unit_id')
                                    ->label(__('Product & Unit'))
                                    ->options(function () {
                                        return \App\Models\ProductUnit::query()
                                            ->where('store_id', Filament::getTenant()->id)
                                            ->with(['unit', 'product'])
                                            ->get()
                                            ->mapWithKeys(function ($productUnit) {
                                                $label = "{$productUnit->product->name} - {$productUnit->unit->name} ({$productUnit->unit->key} - {$productUnit->price} XOF - Qty: {$productUnit->quantity})";
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
                                    ->default(1)
                                    ->minValue(1)
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
            $qty = (int) ($item['quantity'] ?? 1);

            if ($unitId) {
                $productUnit = \App\Models\ProductUnit::find($unitId);
                if ($productUnit) {
                    $total += (float) $productUnit->price * $qty;
                }
            }
        }

        $set('price', number_format($total, 2, '.', ''));
    }

    public static function beforeSave(array $data): array
    {
        // Calculate total price from pack products
        $totalPrice = 0.0;

        if (isset($data['packProducts']) && is_array($data['packProducts'])) {
            foreach ($data['packProducts'] as $packProduct) {
                if (isset($packProduct['product_unit_id']) && isset($packProduct['quantity'])) {
                    $productUnit = \App\Models\ProductUnit::find($packProduct['product_unit_id']);
                    if ($productUnit) {
                        $quantity = (int) $packProduct['quantity'];
                        $totalPrice += (float) $productUnit->price * $quantity;
                    }
                }
            }
        }

        // Ensure price is set
        $data['price'] = $totalPrice;

        return $data;
    }

    public static function afterSave(array $data, $record): void
    {
        // Recalculate and update price after save to ensure it's correct
        if ($record instanceof \App\Models\Pack) {
            $totalPrice = 0.0;

            foreach ($record->packProducts as $packProduct) {
                $totalPrice += (float) $packProduct->productUnit->price * $packProduct->quantity;
            }

            $record->update(['price' => $totalPrice]);
        }
    }
}
