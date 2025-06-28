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
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;
use Filament\Forms\Get;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;

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
        return __('Sale payment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Sales payments');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label(__('Client'))
                            ->relationship('customer', 'name')
                            ->preload()
                            ->searchable()
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('sale_id')
                            ->label(__('Vente'))
                            ->options(function (\Filament\Forms\Get $get) {
                                $customerId = $get('customer_id');
                                if (!$customerId) return [];
                                return \App\Models\Sale::where('customer_id', $customerId)
                                    ->pluck('invoice_number', 'id');
                            })
                            ->preload()
                            ->searchable()
                            ->required()
                            ->reactive(),
                        Forms\Components\Placeholder::make('sale_due')
                            ->label(__('Montant dû pour cette vente'))
                            ->content(function (\Filament\Forms\Get $get) {
                                $saleId = $get('sale_id');
                                if (!$saleId) return '';
                                $sale = \App\Models\Sale::find($saleId);
                                return $sale ? number_format($sale->amount_due, 0, ',', ' ') . ' XOF' : '';
                            }),
                        Forms\Components\TextInput::make('amount')
                            ->label(__('Montant'))
                            ->numeric()
                            ->required()
                            ->rules([
                                function (\Filament\Forms\Get $get) {
                                    return function ($attribute, $value, $fail) use ($get) {
                                        $saleId = $get('sale_id');
                                        if ($saleId) {
                                            $sale = \App\Models\Sale::find($saleId);
                                            if ($sale && $value > $sale->amount_due) {
                                                $fail('Le montant ne peut pas dépasser le montant dû pour cette vente.');
                                            }
                                        }
                                    };
                                },
                            ]),
                        Forms\Components\Textarea::make('note')
                            ->label(__('Note'))
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn() => \Illuminate\Support\Facades\Auth::id()),
                        Forms\Components\Hidden::make('store_id')
                            ->default(fn() => \Filament\Facades\Filament::getTenant()->id),
                    ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale.invoice_number')
                    ->label(__('Vente'))
                    ->formatStateUsing(fn($state) => $state ?: 'N/A'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label(__('Client'))
                    ->formatStateUsing(fn($state) => $state ?: 'N/A'),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Montant'))
                    ->money('XOF'),
                TextColumn::make('amount_due')
                    ->label(__('Montant dû'))
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                    ->formatStateUsing(function ($state) {
                        if ($state == 0) {
                            return 'Aucun dû';
                        }
                        return number_format($state, 0, ',', ' ') . ' XOF';
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->label(__('Statut'))
                    ->getStateUsing(function ($record) {
                        return ($record->amount_due == 0) ? 'Payé' : 'Impayé';
                    })
                    ->colors([
                        'success' => fn($state) => $state === 'Payé',
                        'danger' => fn($state) => $state === 'Impayé',
                    ]),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Utilisateur'))
                    ->formatStateUsing(fn($state) => $state ?: 'N/A'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime(),
                Tables\Columns\TextColumn::make('note')
                    ->label(__('Note'))
                    ->formatStateUsing(fn($state) => $state ?: 'N/A'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'paid' => 'Payé',
                        'unpaid' => 'Impayé',
                    ])
                    ->native(false)
                    ->searchable(),
                Tables\Filters\SelectFilter::make('sale_id')
                    ->label(__('Vente'))
                    ->options(Sale::all()->pluck('invoice_number', 'id'))
                    ->native(false)
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label(__('Client'))
                    ->options(Customer::all()->pluck('name', 'id'))
                    ->native(false)
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label(__('Utilisateur'))
                    ->options(User::all()->pluck('name', 'id'))
                    ->native(false)
                    ->searchable()
                    ->preload(),
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
