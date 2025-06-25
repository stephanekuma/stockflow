<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Filament\Resources\PurchaseResource\RelationManagers;
use App\Models\ProductUnit;
use App\Models\Purchase;
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

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-vertical';

    public static function getNavigationGroup(): ?string
    {
        return __('Transactions');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Provider Information')
                    ->heading(__('Provider Information'))
                    ->collapsible()
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('provider_id')
                            ->label(__('Provider'))
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->relationship('provider', 'name')
                            ->required()
                            ->createOptionForm(fn() => array_merge(
                                ProviderResource::getFormSchema(),
                                [
                                    Forms\Components\Hidden::make('store_id')
                                        ->default(Filament::getTenant()->id),
                                ]
                            ))
                            ->createOptionModalHeading(__('Create New Provider')),
                        Forms\Components\TextInput::make('invoice_number')
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('purchased_at')
                            ->required(),
                    ]),
                Forms\Components\Section::make('Products Information')
                    ->heading(__('Products Information'))
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('purchasedProducts')
                            ->label(__('Product\'s Units'))
                            ->addActionLabel(__('Add Product To Purchase'))
                            ->collapsible()
                            ->defaultItems(1)
                            ->cloneable()
                            ->reorderableWithButtons()
                            ->columns(3)
                            ->relationship('purchasedProducts')
                            ->orderColumn('product_unit_id')
                            ->schema([
                                Forms\Components\Select::make('product_unit_id')
                                    ->label(__('Product Unit'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->reactive()
                                    ->options(
                                        fn() => ProductUnit::query()
                                            ->whereHas('product', function ($query) {
                                                $query->where('store_id', Filament::getTenant()->id);
                                            })
                                            ->with(['unit', 'product'])
                                            ->get()
                                            ->mapWithKeys(function ($productUnit) {
                                                $label = "{$productUnit->product->name} ({$productUnit->unit->key} - {$productUnit->price})";
                                                return [$productUnit->id => $label];
                                            })
                                    )
                                    ->createOptionForm(fn() => array_merge(
                                        ProductResource::getFormSchema(),
                                        [
                                            Forms\Components\Hidden::make('store_id')
                                                ->default(Filament::getTenant()->id),
                                        ]
                                    ))
                                    ->createOptionModalHeading(__('Create New Product'))
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $productUnit = ProductUnit::find($get('product_unit_id'));
                                        $set('price', $productUnit->price);
                                    }),
                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->suffix('XOF')
                                    ->required()
                                    ->readOnly()
                                    ->reactive()
                                    ->afterStateUpdated(fn(Set $set, Get $get) => $set('total', $get('quantity') * $get('price'))),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn(Set $set, Get $get) => $set('total', $get('quantity') * $get('price'))),
                                Forms\Components\TextInput::make('vat')
                                    ->numeric()
                                    ->suffix('%')
                                    ->required(),
                                Forms\Components\TextInput::make('discount')
                                    ->numeric()
                                    ->suffix('%')
                                    ->required(),
                                Forms\Components\TextInput::make('total')
                                    ->numeric()
                                    ->suffix('XOF')
                                    ->required()
                                    ->default(function (Get $get, Set $set) {
                                        $set('total', $get('quantity') * $get('price'));
                                    })
                                    ->readOnly(),
                            ]),
                    ]),
                Forms\Components\TextInput::make('total')
                    ->numeric(),
                Forms\Components\TextInput::make('discount')
                    ->numeric(),
                Forms\Components\TextInput::make('data'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchased_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
