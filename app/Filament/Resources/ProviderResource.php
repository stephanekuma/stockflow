<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderResource\Pages;
use App\Filament\Resources\ProviderResource\RelationManagers;
use App\Models\Provider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProviderResource extends CustomerResource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    public static function getNavigationGroup(): ?string
    {
        return __('Business Entities');
    }

    public static function getModelLabel(): string
    {
        return __('Provider');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Providers');
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
            'index' => Pages\ListProviders::route('/'),
            // 'create' => Pages\CreateProvider::route('/create'),
            // 'edit' => Pages\EditProvider::route('/{record}/edit'),
        ];
    }
}
