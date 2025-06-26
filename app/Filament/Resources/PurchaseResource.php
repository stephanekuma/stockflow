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

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('Transactions');
    }

    public static function getModelLabel(): string
    {
        return __('Purchase');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Purchases');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Purchase Information'))
                    ->schema([
                        Forms\Components\Grid::make(3)
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
                                    ->label(__('Invoice Number'))
                                    ->maxLength(255)
                                    ->placeholder(__('Leave empty to auto-generate'))
                                    ->helperText(__('If left empty, an invoice number will be generated automatically'))
                                    ->unique(ignoreRecord: true),
                                Forms\Components\DateTimePicker::make('purchased_at')
                                    ->label(__('Purchase Date'))
                                    ->default(now())
                                    ->required(),
                            ]),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('Products Information'))
                    ->schema([
                        Forms\Components\Repeater::make('purchasedProducts')
                            ->label(__('Purchased Products'))
                            ->addActionLabel(__('Add Product'))
                            ->collapsible()
                            ->defaultItems(1)
                            ->cloneable()
                            ->reorderableWithButtons()
                            ->columns(4)
                            ->schema([
                                Forms\Components\Select::make('product_unit_id')
                                    ->label(__('Product & Unit'))
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
                                                $label = "{$productUnit->product->name} - {$productUnit->unit->name} ({$productUnit->unit->key} - {$productUnit->price} XOF - Stock: {$productUnit->quantity})";
                                                return [$productUnit->id => $label];
                                            })
                                    )
                                    ->createOptionAction(
                                        fn(Forms\Components\Actions\Action $action) => $action
                                            ->modalHeading(__('Create New Product'))
                                            ->modalSubmitActionLabel(__('Create Product'))
                                            ->form([
                                                Forms\Components\Select::make('category_id')
                                                    ->label(__('Category'))
                                                    ->options(fn() => \App\Models\Category::query()
                                                        ->where('store_id', Filament::getTenant()->id)
                                                        ->pluck('name', 'id'))
                                                    ->required(),
                                                Forms\Components\Select::make('brand_id')
                                                    ->label(__('Brand'))
                                                    ->options(fn() => \App\Models\Brand::query()
                                                        ->where('store_id', Filament::getTenant()->id)
                                                        ->pluck('name', 'id'))
                                                    ->required(),
                                                Forms\Components\TextInput::make('name')
                                                    ->label(__('Product Name'))
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('sku')
                                                    ->label('SKU')
                                                    ->maxLength(255),
                                                Forms\Components\Textarea::make('description')
                                                    ->label(__('Description'))
                                                    ->rows(3),
                                                Forms\Components\Select::make('unit_id')
                                                    ->label(__('Unit'))
                                                    ->options(fn() => \App\Models\Unit::query()
                                                        ->where('store_id', Filament::getTenant()->id)
                                                        ->pluck('name', 'id'))
                                                    ->required(),
                                                Forms\Components\TextInput::make('cost_price')
                                                    ->label(__('Cost Price'))
                                                    ->numeric()
                                                    ->prefix('XOF')
                                                    ->required(),
                                                Forms\Components\TextInput::make('price')
                                                    ->label(__('Sale Price'))
                                                    ->numeric()
                                                    ->prefix('XOF')
                                                    ->required(),
                                                Forms\Components\TextInput::make('quantity')
                                                    ->label(__('Initial Stock'))
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required(),
                                            ])
                                            ->mutateFormDataUsing(function (array $data): array {
                                                $data['store_id'] = Filament::getTenant()->id;
                                                return $data;
                                            })
                                            ->using(function (array $data, string $model): string {
                                                // Create the product first
                                                $product = \App\Models\Product::create([
                                                    'store_id' => $data['store_id'],
                                                    'category_id' => $data['category_id'],
                                                    'brand_id' => $data['brand_id'],
                                                    'name' => $data['name'],
                                                    'sku' => $data['sku'] ?? null,
                                                    'description' => $data['description'] ?? null,
                                                ]);

                                                // Create the product unit
                                                $productUnit = \App\Models\ProductUnit::create([
                                                    'store_id' => $data['store_id'],
                                                    'product_id' => $product->id,
                                                    'unit_id' => $data['unit_id'],
                                                    'cost_price' => $data['cost_price'],
                                                    'price' => $data['price'],
                                                    'quantity' => $data['quantity'],
                                                ]);

                                                return $productUnit->id;
                                            })
                                    )
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $productUnit = ProductUnit::find($get('product_unit_id'));
                                        if ($productUnit) {
                                            $set('cost_price', $productUnit->cost_price ?? 0);
                                            $set('price', $productUnit->price);
                                            self::calculateProductTotal($get, $set);
                                        }
                                    }),

                                Forms\Components\TextInput::make('quantity')
                                    ->label(__('Quantity'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1)
                                    ->reactive()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateProductTotal($get, $set)),

                                Forms\Components\TextInput::make('cost_price')
                                    ->label(__('Cost Price'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateProductTotal($get, $set)),

                                Forms\Components\TextInput::make('price')
                                    ->label(__('Sale Price'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateProductTotal($get, $set)),

                                Forms\Components\TextInput::make('discount')
                                    ->label(__('Discount'))
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->reactive()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateProductTotal($get, $set)),

                                Forms\Components\TextInput::make('vat')
                                    ->label(__('VAT'))
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->reactive()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateProductTotal($get, $set)),

                                Forms\Components\TextInput::make('total')
                                    ->label(__('Total'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->required()
                                    ->readOnly()
                                    ->reactive(),
                            ])
                            ->afterStateUpdated(fn(Get $get, Set $set) => self::calculatePurchaseTotal($get, $set))
                            ->afterStateHydrated(fn(Get $get, Set $set) => self::calculatePurchaseTotal($get, $set))
                            ->live()
                            ->afterStateUpdated(fn(Get $get, Set $set) => self::calculatePurchaseTotal($get, $set)),
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
                    ->afterStateHydrated(fn(Get $get, Set $set) => self::calculatePurchaseTotal($get, $set)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider.name')
                    ->label(__('Provider'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label(__('Invoice'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchased_at')
                    ->label(__('Date'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label(__('Subtotal'))
                    ->money(currency: 'XOF', locale: 'fr')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount')
                    ->label(__('Discount'))
                    ->money(currency: 'XOF', locale: 'fr')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label(__('Total'))
                    ->money(currency: 'XOF', locale: 'fr')
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->relationship('provider', 'name'),
                Tables\Filters\Filter::make('purchased_at')
                    ->form([
                        Forms\Components\DatePicker::make('purchased_from'),
                        Forms\Components\DatePicker::make('purchased_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['purchased_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('purchased_at', '>=', $date),
                            )
                            ->when(
                                $data['purchased_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('purchased_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label(__('Print'))
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn(Purchase $record): string => static::getUrl('print', ['record' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('purchased_at', 'desc');
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
            'print' => Pages\PrintPurchase::route('/{record}/print'),
        ];
    }

    public static function calculateProductTotal(Get $get, Set $set): void
    {
        $quantity = (float) ($get('quantity') ?? 0);
        $price = (float) ($get('price') ?? 0);
        $discount = (float) ($get('discount') ?? 0);
        $vat = (float) ($get('vat') ?? 0);

        $subtotal = $quantity * $price;
        $discountAmount = $subtotal * ($discount / 100);
        $vatAmount = ($subtotal - $discountAmount) * ($vat / 100);
        $total = $subtotal - $discountAmount + $vatAmount;

        $set('total', number_format($total, 2, '.', ''));
    }

    public static function calculatePurchaseTotal(Get $get, Set $set): void
    {
        $products = $get('purchasedProducts') ?? [];
        $subtotal = 0.0;
        $totalDiscount = 0.0;

        foreach ($products as $product) {
            $quantity = (float) ($product['quantity'] ?? 0);
            $price = (float) ($product['price'] ?? 0);
            $discount = (float) ($product['discount'] ?? 0);
            $vat = (float) ($product['vat'] ?? 0);

            $productSubtotal = $quantity * $price;
            $discountAmount = $productSubtotal * ($discount / 100);
            $vatAmount = ($productSubtotal - $discountAmount) * ($vat / 100);
            $productTotal = $productSubtotal - $discountAmount + $vatAmount;

            $subtotal += $productSubtotal; // Subtotal sans remises ni TVA
            $totalDiscount += $discountAmount; // Somme des remises
        }

        $total = $subtotal - $totalDiscount; // Total = Subtotal - Total des remises

        // Set the hidden fields with calculated values
        $set('subtotal', $subtotal);
        $set('discount', $totalDiscount); // Total discount calculé automatiquement
        $set('total', $total);
    }

    public static function beforeSave(array $data): array
    {
        \Illuminate\Support\Facades\Log::info('Purchase beforeSave - Raw data received', [
            'data_keys' => array_keys($data),
            'subtotal' => $data['subtotal'] ?? 'not_set',
            'discount' => $data['discount'] ?? 'not_set',
            'total' => $data['total'] ?? 'not_set',
        ]);

        // Generate invoice number if not provided
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = self::generateInvoiceNumber();
        }

        // Use the values that are already calculated in the form
        // Only recalculate if they are not present
        if (!isset($data['subtotal']) || !isset($data['discount']) || !isset($data['total'])) {
            $subtotal = 0.0;
            $totalDiscount = 0.0;

            if (isset($data['purchasedProducts']) && is_array($data['purchasedProducts'])) {
                foreach ($data['purchasedProducts'] as $product) {
                    $quantity = (float) ($product['quantity'] ?? 0);
                    $price = (float) ($product['price'] ?? 0);
                    $discount = (float) ($product['discount'] ?? 0);

                    $productSubtotal = $quantity * $price;
                    $discountAmount = $productSubtotal * ($discount / 100);

                    $subtotal += $productSubtotal;
                    $totalDiscount += $discountAmount;
                }
            }

            $total = $subtotal - $totalDiscount;

            $data['subtotal'] = $subtotal;
            $data['discount'] = $totalDiscount;
            $data['total'] = $total;

            \Illuminate\Support\Facades\Log::info('Purchase beforeSave - Values recalculated', [
                'subtotal' => $subtotal,
                'discount' => $totalDiscount,
                'total' => $total,
            ]);
        } else {
            \Illuminate\Support\Facades\Log::info('Purchase beforeSave - Using existing calculated values', [
                'subtotal' => $data['subtotal'],
                'discount' => $data['discount'],
                'total' => $data['total'],
            ]);
        }

        return $data;
    }

    public static function generateInvoiceNumber(): string
    {
        $lastPurchase = Purchase::where('store_id', Filament::getTenant()->id)
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = $lastPurchase ? (int) preg_replace('/[^0-9]/', '', $lastPurchase->invoice_number) : 0;
        $newNumber = $lastNumber + 1;

        return 'PUR-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    public static function afterSave(array $data, $record): void
    {
        // Log the saved record for verification
        if ($record instanceof Purchase) {
            \Illuminate\Support\Facades\Log::info('Purchase afterSave - Record saved', [
                'purchase_id' => $record->id,
                'subtotal' => $record->subtotal,
                'discount' => $record->discount,
                'total' => $record->total,
                'invoice_number' => $record->invoice_number,
            ]);
        }

        // Update product unit quantities after purchase
        if ($record instanceof Purchase && isset($data['purchasedProducts'])) {
            foreach ($data['purchasedProducts'] as $purchasedProduct) {
                if (isset($purchasedProduct['product_unit_id']) && isset($purchasedProduct['quantity'])) {
                    $productUnit = ProductUnit::find($purchasedProduct['product_unit_id']);
                    if ($productUnit) {
                        $newQuantity = $productUnit->quantity + (int) $purchasedProduct['quantity'];
                        $productUnit->update(['quantity' => $newQuantity]);

                        \Illuminate\Support\Facades\Log::info('Product unit quantity updated', [
                            'product_unit_id' => $productUnit->id,
                            'old_quantity' => $productUnit->quantity,
                            'added_quantity' => $purchasedProduct['quantity'],
                            'new_quantity' => $newQuantity,
                        ]);
                    }
                }
            }
        }
    }
}
