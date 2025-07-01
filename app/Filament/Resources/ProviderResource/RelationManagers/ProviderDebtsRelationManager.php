<?php

namespace App\Filament\Resources\ProviderResource\RelationManagers;

use App\Models\ProviderDebt;
use App\Models\ProviderPayment;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class ProviderDebtsRelationManager extends RelationManager
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
                TextColumn::make('purchase.invoice_number')->label('Achat'),
                TextColumn::make('amount')->label('Montant total')->money('XOF'),
                TextColumn::make('paid')->label('Payé')->money('XOF'),
                TextColumn::make('remaining')->label('Reste à payer')->money('XOF'),
                TextColumn::make('due_date')->label('Échéance')->date(),
                TextColumn::make('status')->label('Statut')
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
                    ->visible(fn(ProviderDebt $record) => $record->status !== 'paid')
                    ->form([
                        Forms\Components\Placeholder::make('debt_info')
                            ->label('Informations sur la dette')
                            ->content(function (ProviderDebt $record) {
                                $remaining = $record->amount - $record->paid;
                                return "Montant total: " . number_format($record->amount, 0, ',', ' ') . " XOF\n" .
                                    "Déjà payé: " . number_format($record->paid, 0, ',', ' ') . " XOF\n" .
                                    "Reste à payer: " . number_format($remaining, 0, ',', ' ') . " XOF";
                            }),
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant du paiement')
                            ->numeric()
                            ->required()
                            ->default(function (ProviderDebt $record) {
                                return $record->amount - $record->paid;
                            })
                            ->rules([
                                function (ProviderDebt $record) {
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
                                'other' => 'Autre',
                            ])
                            ->default('cash')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ])
                    ->action(function (array $data, ProviderDebt $record) {
                        // Créer le paiement
                        ProviderPayment::create([
                            'store_id' => \Filament\Facades\Filament::getTenant()->id,
                            'provider_id' => $record->provider_id,
                            'provider_debt_id' => $record->id,
                            'amount' => $data['amount'],
                            'payment_date' => $data['payment_date'],
                            'method' => $data['method'],
                            'notes' => $data['notes'],
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
