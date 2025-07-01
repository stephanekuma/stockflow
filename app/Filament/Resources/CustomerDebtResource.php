<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerDebtResource\Pages;
use App\Filament\Resources\CustomerDebtResource\RelationManagers;
use App\Models\CustomerDebt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class CustomerDebtResource extends Resource
{
    protected static ?string $model = CustomerDebt::class;

    // protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?int $navigationSort = 1;

    protected static ?string $tenantOwnershipRelationshipName = 'store';

    public static function getNavigationGroup(): string
    {
        return __('Finances');
    }

    public static function getModelLabel(): string
    {
        return __('Customer Debt');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Customer Debts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de la dette')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Client')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('sale_id')
                            ->label('Vente')
                            ->relationship('sale', 'invoice_number')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Montant total')
                            ->numeric()
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('paid')
                            ->label('Montant payé')
                            ->numeric()
                            ->default(0)
                            ->disabled(),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Date d\'échéance')
                            ->default(now()->addDays(30)),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'unpaid' => 'Impayé',
                                'partial' => 'Partiel',
                                'paid' => 'Payé',
                            ])
                            ->default('unpaid')
                            ->disabled(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sale.invoice_number')
                    ->label('Vente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Montant total')
                    ->money('XOF')
                    ->sortable(),

                TextColumn::make('paid')
                    ->label('Payé')
                    ->money('XOF')
                    ->sortable(),

                TextColumn::make('remaining')
                    ->label('Reste à payer')
                    ->money('XOF')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date()
                    ->sortable()
                    ->color(fn($record) => $record->is_overdue ? 'danger' : 'success'),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'unpaid' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'paid' => 'Payé',
                        'partial' => 'Partiel',
                        'unpaid' => 'Impayé',
                    }),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Client')
                    ->relationship('customer', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'unpaid' => 'Impayé',
                        'partial' => 'Partiel',
                        'paid' => 'Payé',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->label('En retard')
                    ->query(fn(Builder $query): Builder => $query->where('due_date', '<', now())->where('status', '!=', 'paid')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('pay')
                    ->label('Régler')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(fn(CustomerDebt $record) => $record->status !== 'paid')
                    ->form([
                        Forms\Components\Placeholder::make('debt_info')
                            ->label('Informations sur la dette')
                            ->content(function (CustomerDebt $record) {
                                $remaining = $record->amount - $record->paid;
                                return "Client: {$record->customer->name}\n" .
                                    "Vente: {$record->sale->invoice_number}\n" .
                                    "Montant total: " . number_format($record->amount, 0, ',', ' ') . " XOF\n" .
                                    "Déjà payé: " . number_format($record->paid, 0, ',', ' ') . " XOF\n" .
                                    "Reste à payer: " . number_format($remaining, 0, ',', ' ') . " XOF";
                            }),
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant du paiement')
                            ->numeric()
                            ->required()
                            ->default(function (CustomerDebt $record) {
                                return $record->amount - $record->paid;
                            })
                            ->rules([
                                function (CustomerDebt $record) {
                                    return function ($attribute, $value, $fail) use ($record) {
                                        $remaining = $record->amount - $record->paid;
                                        if ($value > $remaining) {
                                            $fail("Le montant ne peut pas dépasser le reste à payer (" . number_format($remaining, 0, ',', ' ') . " XOF)");
                                        }
                                    };
                                },
                            ]),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Date de paiement')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('method')
                            ->label('Méthode de paiement')
                            ->options([
                                'cash' => 'Espèces',
                                'bank_transfer' => 'Virement bancaire',
                                'check' => 'Chèque',
                                'mobile_money' => 'Mobile Money',
                                'deposit' => 'Dépôt client',
                                'other' => 'Autre',
                            ])
                            ->default('cash')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ])
                    ->action(function (array $data, CustomerDebt $record) {
                        // Créer le paiement
                        \App\Models\SalePayment::create([
                            'store_id' => \Filament\Facades\Filament::getTenant()->id,
                            'sale_id' => $record->sale_id,
                            'customer_id' => $record->customer_id,
                            'amount' => $data['amount'],
                            'user_id' => \Illuminate\Support\Facades\Auth::id(),
                            'note' => $data['notes'],
                            'type' => $data['method'],
                        ]);

                        // Mettre à jour la dette
                        $record->paid += $data['amount'];
                        if ($record->paid >= $record->amount) {
                            $record->status = 'paid';
                        } elseif ($record->paid > 0) {
                            $record->status = 'partial';
                        }
                        $record->save();

                        Notification::make()
                            ->title('Paiement enregistré')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListCustomerDebts::route('/'),
            'create' => Pages\CreateCustomerDebt::route('/create'),
            'edit' => Pages\EditCustomerDebt::route('/{record}/edit'),
        ];
    }
}
