<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockLossResource\Pages;
use App\Models\StockLoss;
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
use App\Models\LostProduct;
use App\Services\StockMovementService;

class StockLossResource extends Resource
{
    protected static ?string $model = StockLoss::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        $translation = __('Stock Management');
        return is_string($translation) ? $translation : 'Stock Management';
    }

    public static function getModelLabel(): string
    {
        $translation = __('Stock Loss');
        return is_string($translation) ? $translation : 'Stock Loss';
    }

    public static function getPluralModelLabel(): string
    {
        $translation = __('Stock Losses');
        return is_string($translation) ? $translation : 'Stock Losses';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Loss Information'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('loss_number')
                                    ->label(__('Loss Number'))
                                    ->maxLength(255)
                                    ->placeholder(__('Leave empty to auto-generate'))
                                    ->helperText(__('If left empty, a loss number will be generated automatically'))
                                    ->unique(ignoreRecord: true)
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(StockLoss::generateLossNumber()),
                                DateTimePicker::make('lost_at')
                                    ->label(__('Loss Date'))
                                    ->default(now())
                                    ->required(),
                                Select::make('type')
                                    ->label(__('Loss Type'))
                                    ->options(StockLoss::getTypeOptions())
                                    ->default(StockLoss::TYPE_DAMAGED)
                                    ->required()
                                    ->native(false),
                                Select::make('status')
                                    ->label(__('Status'))
                                    ->options(StockLoss::getStatusOptions())
                                    ->default(StockLoss::STATUS_PENDING)
                                    ->required()
                                    ->native(false),
                            ]),
                        Textarea::make('reason')
                            ->label(__('Loss Reason'))
                            ->required()
                            ->rows(3)
                            ->placeholder(__('Explain why these products are being marked as lost')),
                        Textarea::make('notes')
                            ->label(__('Notes'))
                            ->rows(2)
                            ->placeholder(__('Additional notes about this loss')),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('Lost Products'))
                    ->schema([
                        Repeater::make('lostProducts')
                            ->label(__('Lost Products'))
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
                                        $set('loss_amount', 0);
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
                                                StockLossResource::calculateLossAmount($get, $set);
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
                                            StockLossResource::calculateLossAmount($get, $set);
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
                                        StockLossResource::calculateLossAmount($get, $set);
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
                                        StockLossResource::calculateLossAmount($get, $set);
                                    }),

                                Select::make('condition')
                                    ->label(__('Condition'))
                                    ->options(LostProduct::getConditionOptions())
                                    ->default(LostProduct::CONDITION_DAMAGED)
                                    ->required()
                                    ->native(false),

                                TextInput::make('loss_amount')
                                    ->label(__('Loss Amount'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->required()
                                    ->disabled()
                                    ->reactive(),

                                Textarea::make('loss_reason')
                                    ->label(__('Loss Reason'))
                                    ->required()
                                    ->rows(2)
                                    ->placeholder(__('Why is this product being marked as lost?')),

                                Forms\Components\Hidden::make('product_unit_id'),
                            ])
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                StockLossResource::calculateTotalLoss($get, $set);
                            })
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                StockLossResource::calculateTotalLoss($get, $set);
                            }),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make(__('Summary'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_loss')
                                    ->label(__('Total Loss'))
                                    ->numeric()
                                    ->prefix('XOF')
                                    ->disabled()
                                    ->dehydrated()
                                    ->reactive(),
                            ]),
                    ])
                    ->collapsible()
                    ->afterStateHydrated(fn(Get $get, Set $set) => StockLossResource::calculateTotalLoss($get, $set)),

                Hidden::make('store_id')
                    ->default(function () {
                        return Filament::getTenant()->id;
                    }),
                Hidden::make('user_id')
                    ->default(function () {
                        return auth()->id();
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('loss_number')
                    ->label(__('Loss Number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('User'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('lost_at')
                    ->label(__('Loss Date'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn($state) => StockLoss::getTypeOptions()[$state] ?? $state),
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
                    ->formatStateUsing(fn($state) => StockLoss::getStatusOptions()[$state] ?? $state),
                TextColumn::make('total_loss')
                    ->label(__('Total Loss'))
                    ->money('XOF')
                    ->sortable(),
                TextColumn::make('lostProducts_count')
                    ->label(__('Products'))
                    ->counts('lostProducts')
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
                    ->options(StockLoss::getStatusOptions())
                    ->native(false)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options(StockLoss::getTypeOptions())
                    ->native(false)
                    ->multiple(),

                Tables\Filters\Filter::make('lost_at')
                    ->form([
                        Forms\Components\DatePicker::make('lost_from')
                            ->label(__('Lost from')),
                        Forms\Components\DatePicker::make('lost_until')
                            ->label(__('Lost until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['lost_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('lost_at', '>=', $date),
                            )
                            ->when(
                                $data['lost_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('lost_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($record) => $record->status === StockLoss::STATUS_PENDING)
                    ->action(function ($record) {
                        $record->update(['status' => StockLoss::STATUS_APPROVED]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Approve Loss'))
                    ->modalDescription(__('This loss will be approved.'))
                    ->modalSubmitActionLabel(__('Approve')),
                Tables\Actions\Action::make('reject')
                    ->label(__('Reject'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === StockLoss::STATUS_PENDING)
                    ->action(function ($record) {
                        $record->update(['status' => StockLoss::STATUS_REJECTED]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Reject Loss'))
                    ->modalDescription(__('This loss will be rejected.'))
                    ->modalSubmitActionLabel(__('Reject')),
                Tables\Actions\Action::make('complete')
                    ->label(__('Complete'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === StockLoss::STATUS_APPROVED)
                    ->action(function ($record) {
                        $record->update(['status' => StockLoss::STATUS_COMPLETED]);

                        // Mettre à jour le stock (déduire)
                        $stockService = new StockMovementService();
                        foreach ($record->lostProducts as $lostProduct) {
                            if ($lostProduct->productUnit) {
                                $stockService->removeStock(
                                    $lostProduct->productUnit,
                                    $lostProduct->quantity,
                                    'perte',
                                    'Perte #' . $record->loss_number
                                );
                            }
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Complete Loss'))
                    ->modalDescription(__('This loss will be completed and stock will be updated.'))
                    ->modalSubmitActionLabel(__('Complete')),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('lost_at', 'desc');
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
            'index' => Pages\ListStockLosses::route('/'),
            'create' => Pages\CreateStockLoss::route('/create'),
            'edit' => Pages\EditStockLoss::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('store_id', Filament::getTenant()->id)
            ->with(['user', 'lostProducts.productUnit.product', 'lostProducts.productUnit.unit', 'lostProducts.pack']);
    }

    public static function calculateLossAmount(Get $get, Set $set): void
    {
        $quantity = (float) ($get('quantity') ?? 0);
        $price = (float) ($get('price') ?? 0);
        $lossAmount = $quantity * $price;
        $set('loss_amount', number_format($lossAmount, 2, '.', ''));
    }

    public static function calculateTotalLoss(Get $get, Set $set): void
    {
        $products = $get('lostProducts') ?? [];
        $totalLoss = 0.0;

        foreach ($products as $product) {
            $totalLoss += (float) ($product['loss_amount'] ?? 0);
        }

        $set('total_loss', number_format($totalLoss, 2, '.', ''));
    }
}
