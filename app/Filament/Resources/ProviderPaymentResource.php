<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderPaymentResource\Pages;
use App\Models\ProviderPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class ProviderPaymentResource extends Resource
{
    protected static ?string $model = ProviderPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 1;

    protected static ?string $tenantOwnershipRelationshipName = 'store';

    public static function getNavigationGroup(): string
    {
        return __('Finances');
    }

    public static function getModelLabel(): string
    {
        return __('Provider Payment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Provider Payments');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du paiement')
                    ->schema([
                        Forms\Components\Select::make('provider_id')
                            ->label('Fournisseur')
                            ->relationship('provider', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn() => $this->reset('provider_debt_id')),

                        Forms\Components\Select::make('provider_debt_id')
                            ->label('Dette à régler')
                            ->options(function (Forms\Get $get) {
                                $providerId = $get('provider_id');
                                if (!$providerId) return [];

                                return \App\Models\ProviderDebt::where('provider_id', $providerId)
                                    ->where('status', '!=', 'paid')
                                    ->get()
                                    ->mapWithKeys(function ($debt) {
                                        $remaining = $debt->amount - $debt->paid;
                                        return [$debt->id => "Dette #{$debt->id} - {$debt->purchase?->invoice_number} - Reste: " . number_format($remaining, 0, ',', ' ') . ' XOF'];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $debt = \App\Models\ProviderDebt::find($state);
                                    if ($debt) {
                                        $remaining = $debt->amount - $debt->paid;
                                        $set('amount', $remaining);
                                    }
                                }
                            }),

                        Forms\Components\Placeholder::make('debt_info')
                            ->label('Informations sur la dette')
                            ->content(function (Forms\Get $get) {
                                $debtId = $get('provider_debt_id');
                                if (!$debtId) return 'Sélectionnez une dette';

                                $debt = \App\Models\ProviderDebt::with('purchase')->find($debtId);
                                if (!$debt) return 'Dette non trouvée';

                                $remaining = $debt->amount - $debt->paid;
                                return "Montant total: " . number_format($debt->amount, 0, ',', ' ') . " XOF\n" .
                                    "Déjà payé: " . number_format($debt->paid, 0, ',', ' ') . " XOF\n" .
                                    "Reste à payer: " . number_format($remaining, 0, ',', ' ') . " XOF\n" .
                                    "Achat: " . ($debt->purchase?->invoice_number ?? 'N/A');
                            }),

                        Forms\Components\TextInput::make('amount')
                            ->label('Montant du paiement')
                            ->numeric()
                            ->required()
                            ->rules([
                                function () {
                                    return function ($attribute, $value, $fail) {
                                        $debtId = request()->input('provider_debt_id');
                                        if ($debtId && $value) {
                                            $debt = \App\Models\ProviderDebt::find($debtId);
                                            if ($debt) {
                                                $remaining = $debt->amount - $debt->paid;
                                                if ($value > $remaining) {
                                                    $fail("Le montant ne peut pas dépasser le reste à payer ({$remaining} XOF)");
                                                }
                                            }
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
                                'other' => 'Autre',
                            ])
                            ->default('cash')
                            ->required(),

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
                TextColumn::make('provider.name')
                    ->label('Fournisseur')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('debt.purchase.invoice_number')
                    ->label('Achat')
                    ->formatStateUsing(fn($state) => $state ?: 'N/A'),

                TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable(),

                TextColumn::make('payment_date')
                    ->label('Date de paiement')
                    ->date()
                    ->sortable(),

                TextColumn::make('method')
                    ->label('Méthode')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'cash' => 'Espèces',
                            'bank_transfer' => 'Virement',
                            'check' => 'Chèque',
                            'mobile_money' => 'Mobile Money',
                            'other' => 'Autre',
                            default => $state,
                        };
                    })
                    ->colors([
                        'success' => 'cash',
                        'info' => 'bank_transfer',
                        'warning' => 'check',
                        'primary' => 'mobile_money',
                        'gray' => 'other',
                    ]),

                TextColumn::make('debt.remaining')
                    ->label('Reste à payer')
                    ->money('XOF')
                    ->formatStateUsing(function ($record) {
                        if (!$record->debt) return 'N/A';
                        $remaining = $record->debt->amount - $record->debt->paid;
                        return number_format($remaining, 0, ',', ' ') . ' XOF';
                    }),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider_id')
                    ->label('Fournisseur')
                    ->relationship('provider', 'name'),
                Tables\Filters\SelectFilter::make('method')
                    ->label('Méthode de paiement')
                    ->options([
                        'cash' => 'Espèces',
                        'bank_transfer' => 'Virement bancaire',
                        'check' => 'Chèque',
                        'mobile_money' => 'Mobile Money',
                        'other' => 'Autre',
                    ]),
                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Du'),
                        Forms\Components\DatePicker::make('until')->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListProviderPayments::route('/'),
            'create' => Pages\CreateProviderPayment::route('/create'),
            'edit' => Pages\EditProviderPayment::route('/{record}/edit'),
        ];
    }
}
