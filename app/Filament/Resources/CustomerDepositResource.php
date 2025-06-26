<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerDepositResource\Pages;
use App\Filament\Resources\CustomerDepositResource\RelationManagers;
use App\Models\CustomerDeposit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class CustomerDepositResource extends Resource
{
    protected static ?string $model = CustomerDeposit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label(__('Customer'))
                            ->required()
                            ->relationship(
                                'customer',
                                'name',
                                fn($query) => $query->where('store_id', Filament::getTenant()->id)
                            )
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric(),
                        Forms\Components\Textarea::make('note')
                            ->columnSpanFull(),
                    ])->columns(2)
            ]);
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('Customer'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('User'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('store.name')
                    ->label(__('Store'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerDeposits::route('/'),
            'create' => Pages\CreateCustomerDeposit::route('/create'),
            'edit' => Pages\EditCustomerDeposit::route('/{record}/edit'),
        ];
    }
}
