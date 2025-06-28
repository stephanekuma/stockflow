<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class SalePaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $title = 'Paiements';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('store_id')
                ->default(fn() => \Filament\Facades\Filament::getTenant()->id),
            Forms\Components\TextInput::make('amount')
                ->label('Montant')
                ->numeric()
                ->required()
                ->rules([
                    function () {
                        return function ($attribute, $value, $fail) {
                            $sale = $this->getOwnerRecord();
                            if ($sale && $value > $sale->amount_due) {
                                $fail('Le montant ne peut pas dépasser le montant dû pour cette vente.');
                            }
                        };
                    },
                ]),
            Forms\Components\Textarea::make('note')
                ->label('Note'),
        ]);
    }

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $sale = $this->getOwnerRecord();
        $data['customer_id'] = $sale->customer_id;
        return $data;
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('sale.invoice_number')->label('Vente'),
            Tables\Columns\TextColumn::make('amount')->label('Montant')->money('XOF'),
            Tables\Columns\BadgeColumn::make('amount_due')
                ->label('Montant dû')
                ->colors([
                    'danger' => fn($state) => $state > 0,
                    'success' => fn($state) => $state == 0,
                ])
                ->formatStateUsing(function ($state) {
                    if ($state == 0) {
                        return 'Aucun dû';
                    }
                    return number_format($state, 0, ',', ' ') . ' XOF';
                }),
            Tables\Columns\BadgeColumn::make('status')
                ->label('Statut')
                ->getStateUsing(function ($record) {
                    return ($record->amount_due == 0) ? 'Payé' : 'Impayé';
                })
                ->colors([
                    'success' => fn($state) => $state === 'Payé',
                    'danger' => fn($state) => $state === 'Impayé',
                ]),
            Tables\Columns\TextColumn::make('user.name')->label('Utilisateur'),
            Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime(),
            Tables\Columns\TextColumn::make('note')->label('Note'),
        ])->headerActions([
            Tables\Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data) {
                    $sale = $this->getOwnerRecord();
                    $data['customer_id'] = $sale->customer_id;
                    return $data;
                })
                ->after(function ($record, $data) {
                    // Calcul du montant dû après ce paiement
                    $sale = $record->sale;
                    if ($sale) {
                        $totalPaid = $sale->payments()->sum('amount');
                        $amountDue = max(0, $sale->total - $totalPaid);
                        $record->amount_due = $amountDue;
                        $record->save();
                    }
                }),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }
}
