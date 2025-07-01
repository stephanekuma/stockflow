<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\CustomerDebt;
use App\Models\SalePayment;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class CustomerDebtsRelationManager extends RelationManager
{
    protected static string $relationship = 'debts';
    protected static ?string $recordTitleAttribute = 'id';
    protected static ?string $title = 'Dettes';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                // Pas de création/édition directe ici
            ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('sale.invoice_number')->label('Vente'),
                TextColumn::make('amount')->label('Montant total')->money('XOF'),
                TextColumn::make('paid')->label('Payé')->money('XOF'),
                TextColumn::make('remaining')->label('Reste à payer')->money('XOF'),
                TextColumn::make('due_date')->label('Échéance')->date(),
                TextColumn::make('status')->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'unpaid' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Payé',
                        'partial' => 'Partiel',
                        'unpaid' => 'Impayé',
                    }),
                TextColumn::make('notes')->label('Notes')->limit(30),
                TextColumn::make('created_at')->label('Créé le')->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'unpaid' => 'Impayé',
                        'partial' => 'Partiel',
                        'paid' => 'Payé',
                    ]),
            ])
            ->headerActions([
                // Pas d'ajout direct ici
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('pay')
                    ->label('Régler')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(fn (CustomerDebt $record) => $record->status !== 'paid')
                    ->form([
                        Forms\Components\Placeholder::make('debt_info')
                            ->label('Informations sur la dette')
                            ->content(function (CustomerDebt $record) {
                                $remaining = $record->amount - $record->paid;
                                return "Vente: {$record->sale->invoice_number}\n" .
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
                        SalePayment::create([
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
                //
            ]);
    }
}
