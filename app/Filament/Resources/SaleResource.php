<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\ProductUnit;
use App\Models\Store;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use App\Models\SalePayment;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Unit;
use App\Services\UnitConversionService;
use Filament\Tables\Columns\BadgeColumn;
use App\Filament\Resources\CustomerResource;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?int $navigationSort = 4;

    // public static function getNavigationGroup(): ?string
    // {
    //     $translation = __('Sales Management');
    //     return is_string($translation) ? $translation : 'Sales Management';
    // }

    public static function getNavigationGroup(): ?string
    {
        $translation = __('Transactions');
        return is_string($translation) ? $translation : 'Transactions';
    }

    public static function getModelLabel(): string
    {
        $translation = __('Sale');
        return is_string($translation) ? $translation : 'Sale';
    }

    public static function getPluralModelLabel(): string
    {
        $translation = __('Sales');
        return is_string($translation) ? $translation : 'Sales';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Informations client'))
                    ->schema([
                        Forms\Components\Placeholder::make('customer_balance')
                            ->label('Solde client disponible')
                            ->live()
                            ->content(
                                fn(Get $get) => ($customerId = $get('customer_id'))
                                    ? (\App\Models\Customer::find($customerId)?->balance ?? 0) . ' XOF'
                                    : 'Sélectionnez un client'
                            ),
                    ]),
                Forms\Components\Section::make(__('Sale Information'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Select::make('customer_id')
                                    ->label(__('Customer'))
                                    ->native(false)
                                    ->preload()
                                    ->searchable()
                                    ->relationship('customer', 'name')
                                    ->required()
                                    ->createOptionForm(fn() => array_merge(
                                        CustomerResource::getFormSchema(),
                                        [
                                            Forms\Components\Hidden::make('store_id')
                                                ->default(Filament::getTenant()->id),
                                        ]
                                    ))
                                    ->createOptionModalHeading(__('Create New Customer'))
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set) => $set('invoice_number', Sale::generateInvoiceNumber())),
                                TextInput::make('invoice_number')
                                    ->label(__('Invoice Number'))
                                    ->maxLength(255)
                                    ->placeholder(__('Leave empty to auto-generate'))
                                    ->helperText(__('If left empty, an invoice number will be generated automatically'))
                                    ->unique(ignoreRecord: true)
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(Sale::generateInvoiceNumber()),
                                DateTimePicker::make('sold_at')
                                    ->label(__('Sale Date'))
                                    ->default(now())
                                    ->required(),
                                Select::make('status')
                                    ->label(__('Status'))
                                    ->options(Sale::getStatusOptions())
                                    ->default(Sale::STATUS_IN_PROGRESS)
                                    ->required()
                                    ->native(false),
                            ]),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('Products Information'))
                    ->schema([
                        Repeater::make('soldProducts')
                            ->label(__('Sold Products'))
                            ->addActionLabel(__('Add Product'))
                            ->collapsible()
                            ->defaultItems(1)
                            ->cloneable()
                            ->reorderableWithButtons()
                            ->columns(5)
                            ->schema([
                                Select::make('type')
                                    ->native(false)
                                    ->label(__('Type'))
                                    ->options([
                                        'product' => __('Product'),
                                        'pack' => __('Pack'),
                                    ])
                                    ->default('product')
                                    ->reactive()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $type = $get('type');

                                        // Réinitialiser tous les champs dépendants
                                        $set('product_id', null);
                                        $set('unit_id', null);
                                        $set('pack_id', null);
                                        $set('price', 0);
                                        $set('quantity', 1);
                                        $set('product_unit_id', null);
                                        $set('discount', 0);
                                        $set('total', 0);

                                        // Réinitialiser le total
                                        SaleResource::calculateProductTotal($get, $set);
                                    }),

                                Select::make('product_id')
                                    ->label(__('Product'))
                                    ->required(fn(Get $get) => $get('type') === 'product')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->reactive()
                                    ->live()
                                    ->hidden(fn(Get $get) => $get('type') !== 'product')
                                    ->options(
                                        fn() => Product::query()
                                            ->where('store_id', Filament::getTenant()->id)
                                            ->get()
                                            ->mapWithKeys(function ($product) {
                                                return [$product->id => $product->name];
                                            })
                                    )
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        // Réinitialiser les champs dépendants
                                        $set('unit_id', null);
                                        $set('price', 0);
                                        $set('quantity', 1);
                                        $set('product_unit_id', null);
                                        $set('available_units', []);
                                        $set('stock_available', false);

                                        // Réinitialiser le total
                                        SaleResource::calculateProductTotal($get, $set);
                                    }),

                                Select::make('unit_id')
                                    ->label(__('Unit'))
                                    ->required(fn(Get $get) => $get('type') === 'product' && $get('product_id'))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->reactive()
                                    ->hidden(fn(Get $get) => $get('type') !== 'product' || !$get('product_id'))
                                    ->options(function (Get $get) {
                                        $productId = $get('product_id');
                                        if (!$productId) return [];

                                        $productUnits = ProductUnit::where('product_id', $productId)
                                            ->where('store_id', Filament::getTenant()->id)
                                            ->with(['unit', 'product'])
                                            ->get();

                                        return $productUnits->mapWithKeys(function ($productUnit) {
                                            $stockInfo = $productUnit->quantity > 0 ? "Stock: {$productUnit->quantity}" : "Rupture";
                                            $label = "{$productUnit->unit->name} ({$productUnit->unit->key}) - {$productUnit->price} XOF - {$stockInfo}";
                                            return [$productUnit->unit->id => $label];
                                        });
                                    })
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $productId = $get('product_id');
                                        $unitId = $get('unit_id');

                                        if ($productId && $unitId) {
                                            $productUnit = ProductUnit::where('product_id', $productId)
                                                ->where('unit_id', $unitId)
                                                ->where('store_id', Filament::getTenant()->id)
                                                ->first();

                                            if ($productUnit) {
                                                // Remplir automatiquement le prix avec une valeur par défaut si nécessaire
                                                $price = $productUnit->price ?? 0;
                                                $set('price', $price);
                                                $set('quantity', 1);
                                                $set('product_unit_id', $productUnit->id);

                                                // Vérifier la disponibilité du stock
                                                $requestedQuantity = $get('quantity') ?? 1;
                                                $service = new UnitConversionService();
                                                $hasStock = $service->hasEnoughStock($productId, $requestedQuantity, $unitId);
                                                $set('stock_available', $hasStock);

                                                // Calculer le total
                                                SaleResource::calculateProductTotal($get, $set);
                                            } else {
                                                // Si aucun ProductUnit trouvé, réinitialiser le prix
                                                $set('price', 0);
                                                $set('product_unit_id', null);
                                                SaleResource::calculateProductTotal($get, $set);
                                            }
                                        } else {
                                            // Si product_id ou unit_id manquent, réinitialiser le prix
                                            $set('price', 0);
                                            $set('product_unit_id', null);
                                            SaleResource::calculateProductTotal($get, $set);
                                        }
                                    })
                                    ->live(),

                                Select::make('pack_id')
                                    ->label(__('Pack'))
                                    ->required(fn(Get $get) => $get('type') === 'pack')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->reactive()
                                    ->live()
                                    ->hidden(fn(Get $get) => $get('type') !== 'pack')
                                    ->options(
                                        fn() => \App\Models\Pack::query()
                                            ->where('store_id', \Filament\Facades\Filament::getTenant()->id)
                                            ->get()
                                            ->mapWithKeys(function ($pack) {
                                                return [$pack->id => $pack->name . ' (' . $pack->price . ' XOF)'];
                                            })
                                    )
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $pack = \App\Models\Pack::find($get('pack_id'));
                                        if ($pack) {
                                            // Remplir automatiquement le prix du pack avec une valeur par défaut si nécessaire
                                            $price = $pack->price ?? 0;
                                            $set('price', $price);
                                            $set('quantity', 1);
                                            // Calculer le total
                                            SaleResource::calculateProductTotal($get, $set);
                                        } else {
                                            // Si aucun pack trouvé, réinitialiser le prix
                                            $set('price', 0);
                                            SaleResource::calculateProductTotal($get, $set);
                                        }
                                    }),

                                \Filament\Forms\Components\Placeholder::make('pack_components')
                                    ->label(__('Pack Components'))
                                    ->content(function (Get $get) {
                                        $packId = $get('pack_id');
                                        if (!$packId) return null;

                                        $pack = \App\Models\Pack::with(['packProducts.productUnit.product', 'packProducts.productUnit.unit'])
                                            ->where('store_id', \Filament\Facades\Filament::getTenant()->id)
                                            ->find($packId);

                                        if (!$pack || !$pack->packProducts->count()) {
                                            return __('No components found in this pack');
                                        }

                                        $html = '<div class="bg-gray-50 rounded-lg p-3">';
                                        $html .= '<div class="text-xs text-gray-600 mb-2">';
                                        $html .= '<strong>' . $pack->name . '</strong> - ' . number_format($pack->price, 2) . ' XOF';
                                        $html .= '</div>';
                                        $html .= '<div class="space-y-1">';

                                        foreach ($pack->packProducts as $component) {
                                            $html .= '<div class="flex justify-between text-xs text-gray-600">';
                                            $html .= '<span>• ' . ($component->productUnit->product->name ?? 'N/A') . ' (' . ($component->productUnit->unit->name ?? 'N/A') . ')</span>';
                                            $html .= '<span class="font-medium">' . $component->quantity . 'x</span>';
                                            $html .= '</div>';
                                        }

                                        $html .= '</div></div>';
                                        return new \Illuminate\Support\HtmlString($html);
                                    })
                                    ->hidden(fn(Get $get) => $get('type') !== 'pack' || !$get('pack_id'))
                                    ->extraAttributes(['style' => 'margin-top: -10px;']),

                                Placeholder::make('unit_conversion_info')
                                    ->label(__('Unit Conversion Info'))
                                    ->content(function (Get $get) {
                                        $productId = $get('product_id');
                                        $unitId = $get('unit_id');
                                        $quantity = (int) ($get('quantity') ?? 1);

                                        if (!$productId || !$unitId) return null;

                                        $service = new UnitConversionService();

                                        try {
                                            $strategy = $service->getOptimalSellingStrategy($productId, $unitId, $quantity);

                                            if ($strategy['can_sell']) {
                                                $html = '<div class="bg-green-50 border border-green-200 rounded-lg p-3">';
                                                $html .= '<div class="text-green-800 text-sm font-medium">✅ Vente possible</div>';

                                                if (count($strategy['strategy']) > 1) {
                                                    $html .= '<div class="text-green-700 text-xs mt-1">Stratégie de conversion :</div>';
                                                    foreach ($strategy['strategy'] as $item) {
                                                        $html .= '<div class="text-green-700 text-xs">• ' . $item['unit_name'] . ': ' . $item['quantity'] . '</div>';
                                                    }
                                                }

                                                $html .= '</div>';
                                                return new \Illuminate\Support\HtmlString($html);
                                            } else {
                                                $html = '<div class="bg-red-50 border border-red-200 rounded-lg p-3">';
                                                $html .= '<div class="text-red-800 text-sm font-medium">❌ Stock insuffisant</div>';
                                                $html .= '<div class="text-red-700 text-xs">Il manque ' . $strategy['missing_quantity'] . ' unités de base</div>';
                                                $html .= '</div>';
                                                return new \Illuminate\Support\HtmlString($html);
                                            }
                                        } catch (\Exception $e) {
                                            return null;
                                        }
                                    })
                                    ->hidden(fn(Get $get) => $get('type') !== 'product' || !$get('product_id') || !$get('unit_id'))
                                    ->extraAttributes(['style' => 'margin-top: -10px;']),

                                TextInput::make('quantity')
                                    ->label(__('Quantity'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->step(1)
                                    ->default(1)
                                    ->reactive()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        SaleResource::calculateProductTotal($get, $set);

                                        // Mettre à jour l'info de conversion
                                        $productId = $get('product_id');
                                        $unitId = $get('unit_id');
                                        $quantity = (int) ($get('quantity') ?? 1);

                                        if ($productId && $unitId && $quantity > 0) {
                                            $service = new UnitConversionService();
                                            $hasStock = $service->hasEnoughStock($productId, $quantity, $unitId);
                                            $set('stock_available', $hasStock);
                                        }
                                    }),

                                TextInput::make('price')
                                    ->label(__('Unit Price'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->required()
                                    ->minValue(0)
                                    ->step(5)
                                    ->default(0)
                                    ->reactive()
                                    ->live()
                                    ->helperText(__('Prix unitaire - sera rempli automatiquement lors de la sélection du produit et de l\'unité'))
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        // Si le prix est à 0, essayer de le remplir automatiquement
                                        $price = $get('price');
                                        if ($price == 0) {
                                            $productId = $get('product_id');
                                            $unitId = $get('unit_id');
                                            $packId = $get('pack_id');

                                            if ($productId && $unitId) {
                                                $productUnit = ProductUnit::where('product_id', $productId)
                                                    ->where('unit_id', $unitId)
                                                    ->where('store_id', Filament::getTenant()->id)
                                                    ->first();

                                                if ($productUnit && $productUnit->price > 0) {
                                                    $set('price', $productUnit->price);
                                                }
                                            } elseif ($packId) {
                                                $pack = \App\Models\Pack::find($packId);
                                                if ($pack && $pack->price > 0) {
                                                    $set('price', $pack->price);
                                                }
                                            }
                                        }

                                        SaleResource::calculateProductTotal($get, $set);
                                    }),

                                Select::make('discount_type')
                                    ->label(__('Discount Type'))
                                    ->native(false)
                                    ->options([
                                        'amount' => __('Amount'),
                                        'percent' => __('Percent'),
                                    ])
                                    ->default('amount')
                                    ->reactive(),

                                TextInput::make('discount')
                                    ->label(__('Discount'))
                                    ->numeric()
                                    ->suffix('XOF')
                                    ->default(0)
                                    ->minValue(0)
                                    ->reactive()
                                    ->helperText(__('If percent, enter the percentage (ex: 10 for 10%). If amount, enter the value.')),

                                TextInput::make('total')
                                    ->label(__('Total'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->required()
                                    ->disabled()
                                    ->reactive(),

                                Forms\Components\Hidden::make('product_unit_id'),
                                Forms\Components\Hidden::make('stock_available'),
                            ])
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                // Calcul du total selon le type de remise
                                $quantity = (float)($get('quantity') ?? 0);
                                $price = (float)($get('price') ?? 0);
                                $discount = (float)($get('discount') ?? 0);
                                $discountType = $get('discount_type') ?? 'amount';
                                $subtotal = $quantity * $price;
                                $discountValue = $discountType === 'percent' ? ($subtotal * $discount / 100) : $discount;
                                $total = $subtotal - $discountValue;
                                $set('total', number_format($total, 2, '.', ''));
                            })
                            ->afterStateHydrated(fn(Get $get, Set $set) => SaleResource::calculateSaleTotal($get, $set))
                            ->live()
                            ->afterStateUpdated(fn(Get $get, Set $set) => SaleResource::calculateSaleTotal($get, $set)),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('Summary'))
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('subtotal')
                                    ->label(__('Subtotal'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->disabled()
                                    ->dehydrated()
                                    ->reactive(),
                                Forms\Components\TextInput::make('discount')
                                    ->label(__('Total Discount'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->disabled()
                                    ->dehydrated()
                                    ->reactive(),
                                Forms\Components\TextInput::make('total')
                                    ->label(__('Total'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->disabled()
                                    ->dehydrated()
                                    ->reactive(),
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->afterStateHydrated(fn(Get $get, Set $set) => SaleResource::calculateSaleTotal($get, $set)),

                // Forms\Components\Section::make(__('Paiement'))
                //     ->schema([
                //         Forms\Components\TextInput::make('amount_paid')
                //             ->label('Montant payé (hors solde)')
                //             ->numeric()
                //             ->prefix('XOF')
                //             ->default(0)
                //             ->helperText('Montant payé immédiatement (en plus du solde client)'),
                //     ])
                //     ->collapsible(),

                Repeater::make('payments')
                    ->label(__('Payments'))
                    ->addActionLabel(__('Add Payment'))
                    ->collapsible()
                    ->defaultItems(1)
                    ->columns(3)
                    ->schema([
                        Select::make('type')
                            ->label(__('Payment Type'))
                            ->native(false)
                            ->options([
                                'deposit' => __('Deposit/Balance'),
                                'cash' => __('Cash'),
                                'cheque' => __('Cheque'),
                            ])
                            ->required(),
                        TextInput::make('amount')
                            ->label(__('Amount'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('XOF')
                            ->required(),
                        TextInput::make('note')
                            ->label(__('Note')),
                    ])
                    ->columnSpanFull(),

                Hidden::make('store_id')
                    ->default(function () {
                        return Filament::getTenant()->id;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label(__('Customer'))
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn($state) => $state ?: 'N/A'),
                TextColumn::make('invoice_number')
                    ->label(__('Invoice'))
                    ->searchable()
                    ->formatStateUsing(fn($state) => $state ?: 'N/A'),
                TextColumn::make('sold_at')
                    ->label(__('Date'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('subtotal')
                    ->label(__('Subtotal'))
                    ->money(currency: 'XOF', locale: 'fr')
                    ->sortable(),
                TextColumn::make('discount')
                    ->label(__('Discount'))
                    ->money(currency: 'XOF', locale: 'fr')
                    ->sortable(),
                TextColumn::make('total')
                    ->label(__('Total'))
                    ->money(currency: 'XOF', locale: 'fr')
                    ->sortable()
                    ->color('success'),
                TextColumn::make('amount_due')
                    ->label('Montant dû')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                    ->formatStateUsing(function ($state) {
                        if ($state == 0) {
                            return 'Aucun dû';
                        }
                        return number_format($state, 0, ',', ' ') . ' XOF';
                    }),
                TextColumn::make('status')
                    ->label(__('Statut'))
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => Sale::getStatusOptions()[$state] ?? $state),
                TextColumn::make('payment_status')
                    ->badge()
                    ->label(__('Paiement'))
                    ->getStateUsing(function ($record) {
                        return ($record->amount_due == 0) ? 'Payée' : 'Partiellement payée';
                    })
                    ->colors([
                        'success' => fn($state) => $state === 'Payée',
                        'warning' => fn($state) => $state === 'Partiellement payée',
                    ]),
                TextColumn::make('soldProducts_count')
                    ->label(__('Products'))
                    ->counts('soldProducts')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(Sale::getStatusOptions())
                    ->native(false)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->label(__('Customer')),

                Tables\Filters\Filter::make('sold_at')
                    ->form([
                        Forms\Components\DatePicker::make('sold_from')
                            ->label(__('Sold from')),
                        Forms\Components\DatePicker::make('sold_until')
                            ->label(__('Sold until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['sold_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('sold_at', '>=', $date),
                            )
                            ->when(
                                $data['sold_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('sold_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('put_on_hold')
                    ->label(__('Mettre en attente'))
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->visible(fn($record) => $record->status !== Sale::STATUS_PENDING)
                    ->action(function ($record) {
                        $record->update(['status' => Sale::STATUS_PENDING]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Mettre la vente en attente'))
                    ->modalDescription(__('Cette vente sera mise en attente et pourra être reprise plus tard.'))
                    ->modalSubmitActionLabel(__('Mettre en attente')),
                Tables\Actions\Action::make('resume')
                    ->label(__('Reprendre'))
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn($record) => $record->status === Sale::STATUS_PENDING)
                    ->action(function ($record) {
                        $record->update(['status' => Sale::STATUS_IN_PROGRESS]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Reprendre la vente'))
                    ->modalDescription(__('Cette vente sera remise en cours.'))
                    ->modalSubmitActionLabel(__('Reprendre')),
                Tables\Actions\Action::make('print')
                    ->label(__('Print'))
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn(Sale $record): string => static::getUrl('print', ['record' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sold_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\SaleResource\RelationManagers\SalePaymentsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\SaleResource\Widgets\PendingSalesWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
            'print' => Pages\PrintSale::route('/{record}/print'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('store_id', Filament::getTenant()->id)
            ->with(['soldProducts.productUnit.product', 'soldProducts.productUnit.unit', 'soldProducts.pack', 'customer']);
    }

    public static function calculateProductTotal(Get $get, Set $set): void
    {
        $quantity = (float) ($get('quantity') ?? 0);
        $price = (float) ($get('price') ?? 0);
        $discount = (float) ($get('discount') ?? 0);
        $discountType = $get('discount_type') ?? 'amount';
        $subtotal = $quantity * $price;
        $discountValue = $discountType === 'percent' ? ($subtotal * $discount / 100) : $discount;
        $total = $subtotal - $discountValue;
        $set('total', number_format($total, 2, '.', ''));
    }

    /**
     * Méthode de débogage pour vérifier les prix des ProductUnit
     */
    public static function debugProductUnitPrice($productId, $unitId): ?float
    {
        $productUnit = ProductUnit::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->where('store_id', Filament::getTenant()->id)
            ->first();

        return $productUnit ? $productUnit->price : null;
    }

    public static function calculateSaleTotal(Get $get, Set $set): void
    {
        $products = $get('soldProducts') ?? [];
        $subtotal = 0.0;
        $totalDiscount = 0.0;

        foreach ($products as $product) {
            $quantity = (float) ($product['quantity'] ?? 0);
            $price = (float) ($product['price'] ?? 0);
            $discount = (float) ($product['discount'] ?? 0);
            $discountType = $product['discount_type'] ?? 'amount';
            $productSubtotal = $quantity * $price;
            $discountValue = $discountType === 'percent' ? ($productSubtotal * $discount / 100) : $discount;
            $subtotal += $productSubtotal;
            $totalDiscount += $discountValue;
        }

        $total = $subtotal - $totalDiscount;

        $set('subtotal', number_format($subtotal, 2, '.', ''));
        $set('discount', number_format($totalDiscount, 2, '.', ''));
        $set('total', number_format($total, 2, '.', ''));
    }

    public static function beforeSave(array $data): array
    {
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = Sale::generateInvoiceNumber();
        }

        return $data;
    }

    public static function afterSave(array $data, $record): void
    {
        $record->calculateTotals();
    }

    protected function afterCreate(): void
    {
        $sale = $this->getRecord();
        $customer = \App\Models\Customer::find($sale->customer_id);
        $amountPaid = (float) ($this->data['amount_paid'] ?? 0);
        if ($customer) {
            $balance = $customer->balance;
            $toPay = $sale->total;
            $usedBalance = min($balance, $toPay);
            if ($usedBalance > 0) {
                // Paiement automatique via solde
                \App\Models\SalePayment::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $customer->id,
                    'amount' => $usedBalance,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'note' => 'Paiement automatique via solde client',
                ]);
            }
            if ($amountPaid > 0) {
                // Paiement immédiat saisi par l'utilisateur
                \App\Models\SalePayment::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $customer->id,
                    'amount' => $amountPaid,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'note' => 'Paiement immédiat lors de la vente',
                ]);
            }
            // Le reste dû est géré par l'attribut amount_due du modèle Sale
        }
    }
}
