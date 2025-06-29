<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockReturnResource\Pages;
use App\Models\StockReturn;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\ProductUnit;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Pack;
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
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use App\Models\ReturnedProduct;
use App\Services\StockMovementService;

class StockReturnResource extends Resource
{
    protected static ?string $model = StockReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        $translation = __('Stock Management');
        return is_string($translation) ? $translation : 'Stock Management';
    }

    public static function getModelLabel(): string
    {
        $translation = __('Stock Return');
        return is_string($translation) ? $translation : 'Stock Return';
    }

    public static function getPluralModelLabel(): string
    {
        $translation = __('Stock Returns');
        return is_string($translation) ? $translation : 'Stock Returns';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Return Information'))
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
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('sale_id', null);
                                    }),
                                Select::make('sale_id')
                                    ->label(__('Related Sale (Optional)'))
                                    ->native(false)
                                    ->preload()
                                    ->searchable()
                                    ->relationship('sale', 'invoice_number')
                                    ->options(function (Get $get) {
                                        $customerId = $get('customer_id');
                                        if (!$customerId) return [];

                                        return Sale::where('customer_id', $customerId)
                                            ->where('store_id', Filament::getTenant()->id)
                                            ->get()
                                            ->mapWithKeys(function ($sale) {
                                                return [$sale->id => $sale->invoice_number . ' - ' . $sale->total . ' XOF'];
                                            });
                                    })
                                    ->placeholder(__('Select a sale to link this return'))
                                    ->helperText(__('Optional: Link this return to a specific sale')),
                                TextInput::make('return_number')
                                    ->label(__('Return Number'))
                                    ->maxLength(255)
                                    ->placeholder(__('Leave empty to auto-generate'))
                                    ->helperText(__('If left empty, a return number will be generated automatically'))
                                    ->unique(ignoreRecord: true)
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(StockReturn::generateReturnNumber()),
                                DateTimePicker::make('returned_at')
                                    ->label(__('Return Date'))
                                    ->default(now())
                                    ->required(),
                                Select::make('type')
                                    ->label(__('Return Type'))
                                    ->options(StockReturn::getTypeOptions())
                                    ->default(StockReturn::TYPE_RETURN)
                                    ->required()
                                    ->native(false),
                                Select::make('status')
                                    ->label(__('Status'))
                                    ->options(StockReturn::getStatusOptions())
                                    ->default(StockReturn::STATUS_PENDING)
                                    ->required()
                                    ->native(false),
                            ]),
                        Textarea::make('reason')
                            ->label(__('Return Reason'))
                            ->required()
                            ->rows(3)
                            ->placeholder(__('Explain why the customer is returning these products')),
                        Textarea::make('notes')
                            ->label(__('Notes'))
                            ->rows(2)
                            ->placeholder(__('Additional notes about this return')),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('Returned Products'))
                    ->schema([
                        Repeater::make('returnedProducts')
                            ->label(__('Returned Products'))
                            ->addActionLabel(__('Add Product'))
                            ->collapsible()
                            ->defaultItems(1)
                            ->cloneable()
                            ->reorderableWithButtons()
                            ->columns(6)
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
                                    ->live(),

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
                                        $set('unit_id', null);
                                        $set('price', 0);
                                        $set('quantity', 1);
                                        $set('refund_amount', 0);
                                    }),

                                Select::make('unit_id')
                                    ->label(__('Unit'))
                                    ->required(fn(Get $get) => $get('type') === 'product' && $get('product_id'))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->reactive()
                                    ->live()
                                    ->hidden(fn(Get $get) => $get('type') !== 'product' || !$get('product_id'))
                                    ->options(function (Get $get) {
                                        $productId = $get('product_id');
                                        if (!$productId) return [];

                                        $productUnits = ProductUnit::where('product_id', $productId)
                                            ->where('store_id', Filament::getTenant()->id)
                                            ->with(['unit', 'product'])
                                            ->get();

                                        return $productUnits->mapWithKeys(function ($productUnit) {
                                            $label = "{$productUnit->unit->name} ({$productUnit->unit->key}) - {$productUnit->price} XOF";
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
                                                $set('price', $productUnit->price ?? 0);
                                                $set('quantity', 1);
                                                $set('product_unit_id', $productUnit->id);
                                                StockReturnResource::calculateRefundAmount($get, $set);
                                            }
                                        }
                                    }),

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
                                        fn() => Pack::query()
                                            ->where('store_id', Filament::getTenant()->id)
                                            ->get()
                                            ->mapWithKeys(function ($pack) {
                                                return [$pack->id => $pack->name . ' (' . $pack->price . ' XOF)'];
                                            })
                                    )
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $pack = Pack::find($get('pack_id'));
                                        if ($pack) {
                                            $set('price', $pack->price ?? 0);
                                            $set('quantity', 1);
                                            StockReturnResource::calculateRefundAmount($get, $set);
                                        }
                                    }),

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
                                        StockReturnResource::calculateRefundAmount($get, $set);
                                    }),

                                TextInput::make('price')
                                    ->label(__('Unit Price'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->required()
                                    ->minValue(0)
                                    ->step(5)
                                    ->reactive()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        StockReturnResource::calculateRefundAmount($get, $set);
                                    }),

                                Select::make('condition')
                                    ->label(__('Condition'))
                                    ->options(ReturnedProduct::getConditionOptions())
                                    ->default(ReturnedProduct::CONDITION_GOOD)
                                    ->required()
                                    ->native(false)
                                    ->reactive()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        StockReturnResource::calculateRefundAmount($get, $set);
                                    }),

                                TextInput::make('refund_amount')
                                    ->label(__('Refund Amount'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->required()
                                    ->disabled()
                                    ->reactive(),

                                Textarea::make('return_reason')
                                    ->label(__('Return Reason'))
                                    ->required()
                                    ->rows(2)
                                    ->placeholder(__('Why is this product being returned?')),

                                Forms\Components\Hidden::make('product_unit_id'),
                            ])
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                StockReturnResource::calculateTotalRefund($get, $set);
                            })
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                StockReturnResource::calculateTotalRefund($get, $set);
                            }),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('Summary'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_refund')
                                    ->label(__('Total Refund'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->disabled()
                                    ->dehydrated()
                                    ->reactive(),
                            ]),
                    ])
                    ->collapsible()
                    ->afterStateHydrated(fn(Get $get, Set $set) => StockReturnResource::calculateTotalRefund($get, $set)),

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
                TextColumn::make('return_number')
                    ->label(__('Return Number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label(__('Customer'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sale.invoice_number')
                    ->label(__('Related Sale'))
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn($state) => $state ?: 'N/A'),
                TextColumn::make('returned_at')
                    ->label(__('Return Date'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn($state) => StockReturn::getTypeOptions()[$state] ?? $state),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'rejected' => 'danger',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => StockReturn::getStatusOptions()[$state] ?? $state),
                TextColumn::make('total_refund')
                    ->label(__('Total Refund'))
                    ->money('XOF')
                    ->sortable(),
                TextColumn::make('returnedProducts_count')
                    ->label(__('Products'))
                    ->counts('returnedProducts')
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
                    ->options(StockReturn::getStatusOptions())
                    ->native(false)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options(StockReturn::getTypeOptions())
                    ->native(false)
                    ->multiple(),

                Tables\Filters\Filter::make('returned_at')
                    ->form([
                        Forms\Components\DatePicker::make('returned_from')
                            ->label(__('Returned from')),
                        Forms\Components\DatePicker::make('returned_until')
                            ->label(__('Returned until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['returned_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('returned_at', '>=', $date),
                            )
                            ->when(
                                $data['returned_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('returned_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => $record->status === StockReturn::STATUS_PENDING)
                    ->action(function ($record) {
                        $record->update(['status' => StockReturn::STATUS_APPROVED]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Approve Return'))
                    ->modalDescription(__('This return will be approved and stock will be updated.'))
                    ->modalSubmitActionLabel(__('Approve')),
                Tables\Actions\Action::make('reject')
                    ->label(__('Reject'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === StockReturn::STATUS_PENDING)
                    ->action(function ($record) {
                        $record->update(['status' => StockReturn::STATUS_REJECTED]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Reject Return'))
                    ->modalDescription(__('This return will be rejected.'))
                    ->modalSubmitActionLabel(__('Reject')),
                Tables\Actions\Action::make('complete')
                    ->label(__('Complete'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === StockReturn::STATUS_APPROVED)
                    ->action(function ($record) {
                        $record->update(['status' => StockReturn::STATUS_COMPLETED]);

                        // Mettre à jour le stock
                        $stockService = new StockMovementService();
                        foreach ($record->returnedProducts as $returnedProduct) {
                            if ($returnedProduct->productUnit) {
                                $stockService->addStock(
                                    $returnedProduct->productUnit,
                                    $returnedProduct->quantity,
                                    'retour',
                                    'Retour #' . $record->return_number
                                );
                            }
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Complete Return'))
                    ->modalDescription(__('This return will be completed and stock will be updated.'))
                    ->modalSubmitActionLabel(__('Complete')),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('returned_at', 'desc');
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
            'index' => Pages\ListStockReturns::route('/'),
            'create' => Pages\CreateStockReturn::route('/create'),
            'edit' => Pages\EditStockReturn::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('store_id', Filament::getTenant()->id)
            ->with(['customer', 'sale', 'returnedProducts.productUnit.product', 'returnedProducts.productUnit.unit', 'returnedProducts.pack']);
    }

    public static function calculateRefundAmount(Get $get, Set $set): void
    {
        $quantity = (float) ($get('quantity') ?? 0);
        $price = (float) ($get('price') ?? 0);
        $condition = $get('condition') ?? ReturnedProduct::CONDITION_GOOD;

        $refundPercentage = match($condition) {
            ReturnedProduct::CONDITION_GOOD => 1.0, // 100% remboursement
            ReturnedProduct::CONDITION_DAMAGED => 0.5, // 50% remboursement
            ReturnedProduct::CONDITION_EXPIRED => 0.0, // 0% remboursement
            default => 0.0,
        };

        $refundAmount = $quantity * $price * $refundPercentage;
        $set('refund_amount', number_format($refundAmount, 2, '.', ''));
    }

    public static function calculateTotalRefund(Get $get, Set $set): void
    {
        $products = $get('returnedProducts') ?? [];
        $totalRefund = 0.0;

        foreach ($products as $product) {
            $totalRefund += (float) ($product['refund_amount'] ?? 0);
        }

        $set('total_refund', number_format($totalRefund, 2, '.', ''));
    }
}
