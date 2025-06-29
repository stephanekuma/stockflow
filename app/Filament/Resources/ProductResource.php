<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Models\Unit;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductResource\RelationManagers\ProductUnitsRelationManager;
use App\Filament\Resources\ProductResource\Widgets\StockHistoryChart;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string
    {
        return __('Products Management');
    }

    public static function getModelLabel(): string
    {
        return __('Product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Products');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->heading(__('Product Information'))
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label(__('Category'))
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->relationship(
                                'category',
                                'name',
                                fn($query) => $query->where('store_id', Filament::getTenant()->id)
                            )
                            ->required()
                            ->createOptionForm(fn() => array_merge(
                                CategoryResource::getFormSchema(),
                                [
                                    Forms\Components\Hidden::make('store_id')
                                        ->default(Filament::getTenant()->id),
                                ]
                            ))
                            ->createOptionModalHeading(__('Create New Category')),
                        Forms\Components\Select::make('brand_id')
                            ->label(__('Brand'))
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->relationship(
                                'brand',
                                'name',
                                fn($query) => $query->where('store_id', Filament::getTenant()->id)
                            )
                            ->required()
                            ->createOptionForm(fn() => array_merge(
                                BrandResource::getFormSchema(),
                                [
                                    Forms\Components\Hidden::make('store_id')
                                        ->default(Filament::getTenant()->id),
                                ]
                            ))
                            ->createOptionModalHeading(__('Create New Brand')),
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sku')
                            ->nullable()
                            ->unique(ignoreRecord: true)
                            ->label('SKU')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label(__('Description'))
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('image')
                            ->image(),
                        Forms\Components\KeyValue::make('data')
                            ->label(__('Extra Details'))
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Informations de Péremption')
                    ->heading('Gestion des Produits Périssables')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('is_perishable')
                            ->label('Produit périssable')
                            ->helperText('Cochez cette case si le produit a une date de péremption')
                            ->reactive(),
                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Date d\'expiration')
                            ->visible(fn($get) => $get('is_perishable'))
                            ->required(fn($get) => $get('is_perishable'))
                            ->minDate(now())
                            ->helperText('Date à laquelle le produit expire'),
                        Forms\Components\TextInput::make('expiry_alert_days')
                            ->label('Jours d\'alerte')
                            ->numeric()
                            ->default(30)
                            ->minValue(1)
                            ->maxValue(365)
                            ->visible(fn($get) => $get('is_perishable'))
                            ->helperText('Nombre de jours avant expiration pour déclencher l\'alerte'),
                        Forms\Components\Textarea::make('expiry_notes')
                            ->label('Notes de péremption')
                            ->visible(fn($get) => $get('is_perishable'))
                            ->helperText('Informations supplémentaires sur la gestion de la péremption')
                            ->columnSpanFull(),
                    ])->columns(2),
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
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('Category'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label(__('Brand'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('Description'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_perishable')
                    ->label('Périssable')
                    ->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Date d\'expiration')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->color(fn($record) => $record->isExpired() ? 'danger' : ($record->isExpiringSoon() ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('expiry_status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'expired' => 'danger',
                        'warning' => 'warning',
                        'good' => 'success',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'expired' => 'Expiré',
                        'warning' => 'Attention',
                        'good' => 'Bon',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('units')
                    ->label(__('Units'))
                    ->formatStateUsing(fn($record) => $record->units->map(fn($u) => $u->unit->name . ' (' . $u->quantity . ')')->join(', '))
                    ->toggleable(isToggledHiddenByDefault: false),
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
                Tables\Filters\SelectFilter::make('expiry_status')
                    ->label('Statut de péremption')
                    ->options([
                        'good' => 'Bon',
                        'warning' => 'Attention',
                        'expired' => 'Expiré',
                    ]),
                Tables\Filters\TernaryFilter::make('is_perishable')
                    ->label('Produits périssables')
                    ->placeholder('Tous les produits')
                    ->trueLabel('Périssables uniquement')
                    ->falseLabel('Non périssables uniquement'),
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
            ProductUnitsRelationManager::class,
            \App\Filament\Resources\ProductResource\RelationManagers\StockHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeFill(array $data): array
    {
        \Illuminate\Support\Facades\Log::info('mutateFormDataBeforeFill called', $data);

        if (isset($data['id'])) {
            $product = \App\Models\Product::with('units')->find($data['id']);
            $data['units_data'] = $product
                ? $product->units->map(function ($unit) {
                    return [
                        'id' => $unit->id,
                        'unit_id' => $unit->unit_id,
                        'quantity' => $unit->quantity,
                        'cost_price' => $unit->cost_price,
                        'price' => $unit->price,
                    ];
                })->toArray()
                : [];
        }

        \Illuminate\Support\Facades\Log::info('mutateFormDataBeforeFill result', $data);

        return $data;
    }

    public static function getWidgets(): array
    {
        return [
            StockHistoryChart::class,
        ];
    }
}
