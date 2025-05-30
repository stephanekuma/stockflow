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
                        // Forms\Components\Select::make('store_id')
                        //     ->relationship('store', 'name')
                        //     ->required(),
                        Forms\Components\Select::make('category_id')
                            ->label(__('Category'))
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->relationship('category', 'name')
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
                            ->relationship('brand', 'name')
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

                Forms\Components\Section::make()
                    ->heading(__('Pricing Information'))
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('units')
                            ->relationship('units')
                            ->schema([
                                Forms\Components\Hidden::make('store_id')
                                    ->default(Filament::getTenant()->id),
                                Forms\Components\Select::make('unit_id')
                                    ->label(__('Unit'))
                                    ->native(false)
                                    ->preload()
                                    ->searchable()
                                    ->options(fn() => Unit::query()->pluck('name', 'id')->toArray())
                                    ->required()
                                    ->createOptionForm(fn() => array_merge(
                                        UnitResource::getFormSchema(),
                                        [
                                            Forms\Components\Hidden::make('store_id')
                                                ->default(Filament::getTenant()->id),
                                        ]
                                    ))
                                    ->createOptionModalHeading(__('Create New Unit')),
                                Forms\Components\TextInput::make('quantity')
                                    ->label(__('Quantity'))
                                    ->numeric()
                                    ->required(),
                                Forms\Components\TextInput::make('cost_price')
                                    ->label(__('Cost Price'))
                                    ->numeric()
                                    ->required(),
                                Forms\Components\TextInput::make('price')
                                    ->label(__('Price'))
                                    ->numeric()
                                    ->required(),
                                // Forms\Components\TextInput::make('discount')
                                //     ->label(__('Discount'))
                                //     ->numeric(),
                                // Forms\Components\TextInput::make('vat')
                                //     ->label(__('VAT'))
                                //     ->numeric(),
                                // Forms\Components\TextInput::make('total')
                                //     ->label(__('Total'))
                                //     ->numeric(),
                                // Forms\Components\KeyValue::make('data')
                                //     ->label(__('Extra Details'))
                                //     ->columnSpanFull(),
                            ])
                            ->reorderableWithButtons()
                            ->defaultItems(1)
                            ->cloneable()
                            ->collapsible()
                            ->orderColumn('unit_id')
                            ->columns(2)
                        // ->grid(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('store.name')
                //     ->numeric()
                //     ->sortable(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
