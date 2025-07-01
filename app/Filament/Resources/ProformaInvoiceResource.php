<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProformaInvoiceResource\Pages;
use App\Models\ProformaInvoice;
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
use App\Models\Product;
use App\Models\Unit;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Notifications\Notification;

class ProformaInvoiceResource extends Resource
{
    protected static ?string $model = ProformaInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 5;

    protected static ?string $tenantRelationshipName = 'proformaInvoices';

    public static function getNavigationGroup(): ?string
    {
        $translation = __('Transactions');
        return is_string($translation) ? $translation : 'Transactions';
    }

    public static function getModelLabel(): string
    {
        $translation = __('Proforma Invoice');
        return is_string($translation) ? $translation : 'Proforma Invoice';
    }

    public static function getPluralModelLabel(): string
    {
        $translation = __('Proforma Invoices');
        return is_string($translation) ? $translation : 'Proforma Invoices';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Basic Information'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Select::make('customer_id')
                                    ->label(__('Customer'))
                                    ->options(Customer::where('store_id', Filament::getTenant()->id)->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn() => null),

                                TextInput::make('invoice_number')
                                    ->label(__('Invoice Number'))
                                    ->placeholder(__('Auto-generated if empty'))
                                    ->disabled(fn($get) => !empty($get('invoice_number'))),

                                DateTimePicker::make('issued_at')
                                    ->label(__('Issue Date'))
                                    ->default(now())
                                    ->required(),

                                DateTimePicker::make('valid_until')
                                    ->label(__('Valid Until'))
                                    ->nullable()
                                    ->helperText(__('Leave empty for no expiration')),

                                Select::make('status')
                                    ->label(__('Status'))
                                    ->options(ProformaInvoice::getStatusOptions())
                                    ->default(ProformaInvoice::STATUS_DRAFT)
                                    ->required()
                                    ->native(false),
                            ]),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('Products Information'))
                    ->description(__('Add products or packs to your proforma invoice'))
                    ->schema([
                        Repeater::make('items')
                            ->label(__('Items'))
                            ->addActionLabel(__('Add Item'))
                            ->collapsible()
                            ->defaultItems(1)
                            ->cloneable()
                            ->reorderableWithButtons()
                            ->columns(6)
                            ->schema([
                                Select::make('type')
                                    ->label(__('Type'))
                                    ->options([
                                        'product' => __('Product'),
                                        'pack' => __('Pack'),
                                    ])
                                    ->default('product')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('product_unit_id', null);
                                        $set('pack_id', null);
                                        $set('product_id', null);
                                        $set('unit_id', null);
                                        $set('price', null);
                                        $set('quantity', null);
                                        $set('discount', null);
                                        $set('total', null);
                                    })
                                    ->columnSpan(1),

                                Select::make('product_id')
                                    ->label(__('Product'))
                                    ->options(Product::whereHas('units', function ($query) {
                                        $query->where('store_id', Filament::getTenant()->id);
                                    })->pluck('name', 'id'))
                                    ->searchable()
                                    ->visible(fn(Get $get) => $get('type') === 'product')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $set('unit_id', null);
                                        $set('price', null);
                                        $set('quantity', null);
                                        $set('discount', null);
                                        $set('total', null);

                                        // Auto-select first available unit
                                        $productId = $get('product_id');
                                        if ($productId) {
                                            $firstUnit = Unit::whereHas('productUnits', function ($query) use ($productId) {
                                                $query->where('product_id', $productId)
                                                    ->where('store_id', Filament::getTenant()->id);
                                            })->first();
                                            if ($firstUnit) {
                                                $set('unit_id', $firstUnit->id);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),

                                Select::make('unit_id')
                                    ->label(__('Unit'))
                                    ->options(function (Get $get) {
                                        $productId = $get('product_id');
                                        if (!$productId) return [];

                                        return Unit::whereHas('productUnits', function ($query) use ($productId) {
                                            $query->where('product_id', $productId)
                                                ->where('store_id', Filament::getTenant()->id);
                                        })->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->visible(fn(Get $get) => $get('type') === 'product')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $productId = $get('product_id');
                                        $unitId = $get('unit_id');
                                        if ($productId && $unitId) {
                                            $price = self::getProductUnitPrice($productId, $unitId);
                                            $set('price', $price);
                                            $set('quantity', 1);
                                        }
                                    })
                                    ->columnSpan(1),

                                Select::make('pack_id')
                                    ->label(__('Pack'))
                                    ->options(\App\Models\Pack::where('store_id', Filament::getTenant()->id)->pluck('name', 'id'))
                                    ->searchable()
                                    ->visible(fn(Get $get) => $get('type') === 'pack')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $packId = $get('pack_id');
                                        if ($packId) {
                                            $pack = \App\Models\Pack::find($packId);
                                            if ($pack) {
                                                $set('price', $pack->price);
                                                $set('quantity', 1);
                                            }
                                        }
                                    })
                                    ->columnSpan(3),

                                TextInput::make('quantity')
                                    ->label(__('Quantity'))
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::calculateProformaTotal($get, $set);
                                    })
                                    ->columnSpan(1),

                                TextInput::make('price')
                                    ->label(__('Unit Price'))
                                    ->numeric()
                                    ->required()
                                    ->suffix('XOF')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::calculateProformaTotal($get, $set);
                                    })
                                    ->columnSpan(1),

                                Select::make('discount_type')
                                    ->label(__('Discount Type'))
                                    ->options([
                                        'amount' => __('Amount'),
                                        'percent' => __('Percent'),
                                    ])
                                    ->default('amount')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('discount', 0);
                                    })
                                    ->columnSpan(1),

                                TextInput::make('discount')
                                    ->label(__('Discount'))
                                    ->numeric()
                                    ->default(0)
                                    ->suffix(function (Get $get) {
                                        return ($get('discount_type') ?? 'amount') === 'percent' ? '%' : 'XOF';
                                    })
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::calculateProformaTotal($get, $set);
                                    })
                                    ->columnSpan(1),

                                TextInput::make('total')
                                    ->label(__('Total'))
                                    ->disabled()
                                    ->suffix('XOF')
                                    ->dehydrated(false)
                                    ->formatStateUsing(function (Get $get) {
                                        $quantity = (float) ($get('quantity') ?? 0);
                                        $price = (float) ($get('price') ?? 0);
                                        $discount = (float) ($get('discount') ?? 0);
                                        $discountType = $get('discount_type') ?? 'amount';
                                        $productSubtotal = $quantity * $price;
                                        $discountValue = $discountType === 'percent' ? ($productSubtotal * $discount / 100) : $discount;
                                        $total = $productSubtotal - $discountValue;
                                        return number_format($total, 2);
                                    })
                                    ->columnSpan(1),

                                Hidden::make('product_unit_id')
                                    ->afterStateHydrated(function (Set $set, Get $get) {
                                        $productId = $get('product_id');
                                        $unitId = $get('unit_id');
                                        if ($productId && $unitId) {
                                            $productUnit = ProductUnit::where('product_id', $productId)
                                                ->where('unit_id', $unitId)
                                                ->where('store_id', Filament::getTenant()->id)
                                                ->first();
                                            if ($productUnit) {
                                                $set('product_unit_id', $productUnit->id);
                                            }
                                        }
                                    }),
                            ])
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculateProformaTotal($get, $set);
                            }),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('Summary'))
                    ->description(__('Review your proforma invoice totals'))
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                TextInput::make('subtotal')
                                    ->label(__('Subtotal'))
                                    ->disabled()
                                    ->suffix('XOF')
                                    ->dehydrated(false)
                                    ->formatStateUsing(function (Get $get) {
                                        $items = $get('items') ?? [];
                                        $subtotal = 0;
                                        foreach ($items as $item) {
                                            $quantity = (float) ($item['quantity'] ?? 0);
                                            $price = (float) ($item['price'] ?? 0);
                                            $subtotal += $quantity * $price;
                                        }
                                        return number_format($subtotal, 2);
                                    }),

                                TextInput::make('total_discount')
                                    ->label(__('Total Discount'))
                                    ->disabled()
                                    ->suffix('XOF')
                                    ->dehydrated(false)
                                    ->formatStateUsing(function (Get $get) {
                                        $items = $get('items') ?? [];
                                        $totalDiscount = 0;
                                        foreach ($items as $item) {
                                            $totalDiscount += (float) ($item['discount'] ?? 0);
                                        }
                                        return number_format($totalDiscount, 2);
                                    }),

                                TextInput::make('grand_total')
                                    ->label(__('Grand Total'))
                                    ->disabled()
                                    ->suffix('XOF')
                                    ->dehydrated(false)
                                    ->formatStateUsing(function (Get $get) {
                                        $items = $get('items') ?? [];
                                        $subtotal = 0;
                                        $totalDiscount = 0;
                                        foreach ($items as $item) {
                                            $quantity = (float) ($item['quantity'] ?? 0);
                                            $price = (float) ($item['price'] ?? 0);
                                            $discount = (float) ($item['discount'] ?? 0);
                                            $subtotal += $quantity * $price;
                                            $totalDiscount += $discount;
                                        }
                                        $grandTotal = $subtotal - $totalDiscount;
                                        return number_format($grandTotal, 2);
                                    }),
                            ]),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('Additional Information'))
                    ->description(__('Add notes or additional information'))
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('Notes'))
                            ->rows(3)
                            ->placeholder(__('Add any additional notes or comments...'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('Invoice Number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label(__('Customer'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('issued_at')
                    ->label(__('Issue Date'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('valid_until')
                    ->label(__('Valid Until'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('No expiration')),

                TextColumn::make('total')
                    ->label(__('Total'))
                    ->money('XOF')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->label(__('Status'))
                    ->colors([
                        'gray' => ProformaInvoice::STATUS_DRAFT,
                        'blue' => ProformaInvoice::STATUS_SENT,
                        'green' => ProformaInvoice::STATUS_ACCEPTED,
                        'red' => ProformaInvoice::STATUS_REJECTED,
                        'yellow' => ProformaInvoice::STATUS_EXPIRED,
                        'purple' => ProformaInvoice::STATUS_CONVERTED,
                    ])
                    ->formatStateUsing(fn(string $state): string => ProformaInvoice::getStatusOptions()[$state] ?? $state),

                TextColumn::make('convertedToSale.invoice_number')
                    ->label(__('Converted to Sale'))
                    ->placeholder(__('Not converted'))
                    ->url(fn($record) => $record->convertedToSale ? route('filament.admin.resources.sales.edit', $record->convertedToSale) : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(ProformaInvoice::getStatusOptions())
                    ->native(false)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->label(__('Customer')),

                Tables\Filters\Filter::make('issued_at')
                    ->form([
                        Forms\Components\DatePicker::make('issued_from')
                            ->label(__('Issued from')),
                        Forms\Components\DatePicker::make('issued_until')
                            ->label(__('Issued until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['issued_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('issued_at', '>=', $date),
                            )
                            ->when(
                                $data['issued_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('issued_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('print')
                    ->label(__('Print'))
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn(\App\Models\ProformaInvoice $record): string => static::getUrl('print', ['record' => $record]))
                    ->openUrlInNewTab(),
                Action::make('convert')
                    ->label(__('Convert to Sale'))
                    ->icon('heroicon-o-arrow-right')
                    ->color('success')
                    ->visible(fn($record) => $record->canBeConverted())
                    ->action(function ($record) {
                        try {
                            $sale = $record->convertToSale();
                            Notification::make()
                                ->title(__('Proforma converted successfully'))
                                ->body(__('Sale') . ': ' . $sale->invoice_number)
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('Error converting proforma'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Convert to Sale'))
                    ->modalDescription(__('Are you sure you want to convert this proforma invoice to a sale? This action cannot be undone.'))
                    ->modalSubmitActionLabel(__('Convert')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListProformaInvoices::route('/'),
            'create' => Pages\CreateProformaInvoice::route('/create'),
            'edit' => Pages\EditProformaInvoice::route('/{record}/edit'),
            'print' => Pages\PrintProformaInvoice::route('/{record}/print'),
        ];
    }

    public static function getProductUnitPrice($productId, $unitId)
    {
        $productUnit = ProductUnit::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->where('store_id', Filament::getTenant()->id)
            ->first();

        return $productUnit ? $productUnit->price : null;
    }

    public static function calculateProformaTotal(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        $subtotal = 0.0;
        $totalDiscount = 0.0;

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $discount = (float) ($item['discount'] ?? 0);
            $discountType = $item['discount_type'] ?? 'amount';
            $productSubtotal = $quantity * $price;
            $discountValue = $discountType === 'percent' ? ($productSubtotal * $discount / 100) : $discount;
            $subtotal += $productSubtotal;
            $totalDiscount += $discountValue;
        }

        $total = $subtotal - $totalDiscount;

        $set('subtotal', number_format($subtotal, 2, '.', ''));
        $set('total_discount', number_format($totalDiscount, 2, '.', ''));
        $set('grand_total', number_format($total, 2, '.', ''));
    }

    public static function beforeSave(array $data): array
    {
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = ProformaInvoice::generateInvoiceNumber();
        }

        return $data;
    }

    public static function afterSave(array $data, $record): void
    {
        $record->calculateTotals();
    }
}
