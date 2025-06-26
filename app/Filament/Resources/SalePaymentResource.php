<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalePaymentResource\Pages;
use App\Filament\Resources\SalePaymentResource\RelationManagers;
use App\Models\SalePayment;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalePaymentResource extends Resource
{
    protected static ?string $model = SalePayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?int $navigationSort = 101;

    public static function getNavigationGroup(): string
    {
        return __('Transactions');
    }

    public static function getModelLabel(): string
    {
        return __('Paiement vente');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Paiements ventes');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('sale_id')
                    ->label(__('Vente'))
                    ->relationship('sale', 'invoice_number', fn(Builder $query) => $query->where('customer_id', request()->get('customer_id')))
                    ->searchable()
                    ->required()
                    ->default(request()->get('sale_id')),
                Forms\Components\Select::make('customer_id')
                    ->label(__('Client'))
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->required()
                    ->default(request()->get('customer_id')),
                Forms\Components\TextInput::make('amount')
                    ->label(__('Montant'))
                    ->numeric()
                    ->required(),
                Forms\Components\Textarea::make('note')
                    ->label(__('Note')),
                Forms\Components\Hidden::make('user_id')
                    ->default(fn() => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale.invoice_number')
                    ->label(__('Vente')),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('Client')),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Montant'))
                    ->money('XOF'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Utilisateur')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime(),
                Tables\Columns\TextColumn::make('note')
                    ->label(__('Note')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSalePayments::route('/'),
            'create' => Pages\CreateSalePayment::route('/create'),
            'view' => Pages\ViewSalePayment::route('/{record}'),
            'edit' => Pages\EditSalePayment::route('/{record}/edit'),
        ];
    }
}
