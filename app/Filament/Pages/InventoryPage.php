<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class InventoryPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static string $view = 'filament.pages.inventory-page';
    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): string
    {
        return __('Stock Management');
    }

    public static function getNavigationLabel(): string
    {
        return __('Inventory');
    }

    // public static function getTitle(): string|Htmlable
    // {
    //     return __('Inventory');
    // }

    public static function getModelLabel(): string
    {
        return __('Inventory');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Inventories');
    }

    public $category = null;
    public $brand = null;

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                Product::query()
            )
            ->columns([
                TextColumn::make('name')->label('Produit')->searchable(),
                TextColumn::make('category.name')->label('Catégorie'),
                TextColumn::make('brand.name')->label('Marque'),
                TextColumn::make('real_stock')
                    ->label(__('Real stock'))
                    ->getStateUsing(fn($record) => $record->units->sum('quantity')),
                TextColumn::make('purchase_price')->label(__('Purchase price'))->money('XOF'),
                TextColumn::make('sale_price')->label(__('Sale price'))->money('XOF'),
                TextColumn::make('stock_value_purchase')->label(__('Purchase value'))->getStateUsing(fn($record) => $record->stock * $record->purchase_price)->money('XOF'),
                TextColumn::make('stock_value_sale')->label(__('Sale value'))->getStateUsing(fn($record) => $record->stock * $record->sale_price)->money('XOF'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->options(Category::pluck('name', 'id')->toArray()),
                SelectFilter::make('brand_id')
                    ->label('Marque')
                    ->options(Brand::pluck('name', 'id')->toArray()),
            ]);
    }

    public function getStats(): array
    {
        $totalStock = \App\Models\ProductUnit::sum('quantity');
        $totalPurchaseValue = \App\Models\ProductUnit::selectRaw('SUM(quantity * cost_price) as total')->value('total');
        $totalSaleValue = \App\Models\ProductUnit::selectRaw('SUM(quantity * price) as total')->value('total');
        // $totalPurchased = ...; // À adapter selon ta structure d'achats
        return [
            'totalStock' => $totalStock,
            'totalPurchaseValue' => $totalPurchaseValue,
            'totalSaleValue' => $totalSaleValue,
            'totalPurchased' => 0, // À calculer si tu as la structure
        ];
    }
}
