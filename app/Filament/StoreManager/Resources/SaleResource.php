<?php

namespace App\Filament\StoreManager\Resources;

use Filament\Resources\Resource;
use App\Models\Sale;
use App\Filament\StoreManager\Resources\SaleResource\Pages;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $slug = 'sales';
    protected static ?string $label = 'Sale';
    protected static ?string $pluralLabel = 'Sales';

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        // À compléter selon besoins
        return $form;
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        // À compléter selon besoins
        return $table;
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
}
