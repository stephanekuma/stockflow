<?php

namespace App\Filament\StoreManager\Resources;

use App\Filament\StoreManager\Resources\StoreResource\Pages;
use App\Filament\StoreManager\Resources\StoreResource\RelationManagers;
use App\Filament\StoreManager\Resources\StoreResource\RelationManagers\UsersRelationManager;
use App\Models\Store;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    public static function getModelLabel(): string
    {
        return __('Store');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stores');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->placeholder(__('Enter store name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('location')
                    ->label(__('Location'))
                    ->placeholder(__('Enter store location'))
                    ->maxLength(255),
                Forms\Components\KeyValue::make('data')
                    ->label(__('Data'))
                    ->keyLabel(__('Key'))
                    ->valueLabel(__('Value'))
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('location')
                    ->label(__('Location'))
                    ->searchable(),
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
            UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStores::route('/'),
            // 'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
