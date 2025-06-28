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

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 6;

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
                            ->content(
                                fn(Get $get) => ($customerId = $get('customer_id'))
                                    ? (\App\Models\Customer::find($customerId)?->balance ?? 0) . ' XOF'
                                    : 'Sélectionnez un client'
                            ),
                    ]),
                Forms\Components\Section::make(__('Sale Information'))
                    ->schema([
                        Forms\Components\Grid::make(3)
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
                            ->columns(4)
                            ->schema([
                                Select::make('type')
                                    ->label('Type')
                                    ->options([
                                        'product' => __('Product'),
                                        'pack' => __('Pack'),
                                    ])
                                    ->default('product')
                                    ->reactive(),

                                Select::make('product_unit_id')
                                    ->label(__('Product & Unit'))
                                    ->required(fn(Get $get) => $get('type') === 'product')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->reactive()
                                    ->hidden(fn(Get $get) => $get('type') !== 'product')
                                    ->options(
                                        fn() => ProductUnit::query()
                                            ->whereHas('product', function ($query) {
                                                $query->where('store_id', Filament::getTenant()->id);
                                            })
                                            ->with(['unit', 'product'])
                                            ->get()
                                            ->mapWithKeys(function ($productUnit) {
                                                $label = "{$productUnit->product->name} - {$productUnit->unit->name} ({$productUnit->unit->key} - {$productUnit->price} XOF - Stock: {$productUnit->quantity})";
                                                return [$productUnit->id => $label];
                                            })
                                    )
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $productUnit = ProductUnit::find($get('product_unit_id'));
                                        if ($productUnit) {
                                            $set('price', $productUnit->price ?? 0);
                                            $set('quantity', 1);
                                            SaleResource::calculateProductTotal($get, $set);
                                        }
                                    }),

                                Select::make('pack_id')
                                    ->label(__('Pack'))
                                    ->required(fn(Get $get) => $get('type') === 'pack')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->reactive()
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
                                            $set('price', $pack->price ?? 0);
                                            $set('quantity', 1);
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

                                TextInput::make('quantity')
                                    ->label(__('Quantity'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1)
                                    ->reactive()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => SaleResource::calculateProductTotal($get, $set)),

                                TextInput::make('price')
                                    ->label(__('Unit Price'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->hidden()
                                    ->reactive()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => SaleResource::calculateProductTotal($get, $set)),

                                TextInput::make('discount')
                                    ->label(__('Discount'))
                                    ->numeric()
                                    ->suffix('XOF')
                                    ->default(0)
                                    ->minValue(0)
                                    ->reactive()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => SaleResource::calculateProductTotal($get, $set)),

                                TextInput::make('total')
                                    ->label(__('Total'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->required()
                                    ->disabled()
                                    ->reactive(),
                            ])
                            ->afterStateUpdated(fn(Get $get, Set $set) => SaleResource::calculateSaleTotal($get, $set))
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

                Forms\Components\Section::make(__('Paiement'))
                    ->schema([
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Montant payé (hors solde)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->reactive(),
                        Forms\Components\Placeholder::make('amount_due_preview')
                            ->label('Reste dû (crédit)')
                            ->content(function (Get $get) {
                                $customerId = $get('customer_id');
                                $total = (float) $get('total');
                                $amountPaid = (float) $get('amount_paid');
                                $balance = $customerId ? (\App\Models\Customer::find($customerId)?->balance ?? 0) : 0;
                                $reste = $total - min($balance, $total) - $amountPaid;
                                return max(0, $reste) . ' XOF';
                            }),
                    ])
                    ->collapsible(),

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
                    ->searchable(),

                TextColumn::make('invoice_number')
                    ->label(__('Invoice'))
                    ->searchable(),

                TextColumn::make('sold_at')
                    ->label(__('Date'))
                    ->dateTime()
                    ->sortable(),

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

                TextColumn::make('soldProducts_count')
                    ->label(__('Products'))
                    ->counts('soldProducts')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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

        $subtotal = $quantity * $price;
        $total = $subtotal - $discount;

        $set('total', number_format($total, 2, '.', ''));
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

            $productSubtotal = $quantity * $price;
            $subtotal += $productSubtotal;
            $totalDiscount += $discount;
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
