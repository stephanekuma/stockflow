<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PayrollsRelationManager extends RelationManager
{
    protected static string $relationship = 'payrolls';
    protected static ?string $recordTitleAttribute = 'id';

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([
            \Filament\Forms\Components\Hidden::make('store_id')
                ->default(Filament::getTenant()->id),
            \Filament\Forms\Components\TextInput::make('base_amount')
                ->label(__('Base Amount'))
                ->required()
                ->numeric()
                ->default(fn($livewire) => $livewire->getOwnerRecord()?->base_salary)
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    $bonus = (float) $get('bonus') ?: 0;
                    $deductions = (float) $get('deductions') ?: 0;
                    $base = (float) $state ?: 0;
                    $set('total_amount', $base + $bonus - $deductions);
                }),
            \Filament\Forms\Components\TextInput::make('bonus')
                ->label(__('Bonus'))
                ->numeric()
                ->default(0)
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    $base = (float) $get('base_amount') ?: 0;
                    $deductions = (float) $get('deductions') ?: 0;
                    $bonus = (float) $state ?: 0;
                    $set('total_amount', $base + $bonus - $deductions);
                }),
            \Filament\Forms\Components\TextInput::make('deductions')
                ->label(__('Deductions'))
                ->numeric()
                ->default(0)
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    $base = (float) $get('base_amount') ?: 0;
                    $bonus = (float) $get('bonus') ?: 0;
                    $deductions = (float) $state ?: 0;
                    $set('total_amount', $base + $bonus - $deductions);
                }),
            \Filament\Forms\Components\TextInput::make('total_amount')
                ->label(__('Total Amount'))
                ->numeric()
                ->prefix('XOF')
                ->disabled()
                ->dehydrated()
                ->reactive()
                ->afterStateHydrated(function ($component, $state, $record, $set, $get) {
                    $base = (float) $get('base_amount') ?: 0;
                    $bonus = (float) $get('bonus') ?: 0;
                    $deductions = (float) $get('deductions') ?: 0;
                    $set('total_amount', $base + $bonus - $deductions);
                }),
            \Filament\Forms\Components\DatePicker::make('payment_date')
                ->label(__('Payment Date'))
                ->required(),
            \Filament\Forms\Components\Select::make('payment_method')
                ->label(__('Payment Method'))
                ->native(false)
                ->options([
                    'cash' => __('Cash'),
                    'bank_transfer' => __('Bank Transfer'),
                    'check' => __('Check'),
                ])
                ->required(),
            \Filament\Forms\Components\Select::make('status')
                ->label(__('Status'))
                ->native(false)
                ->options([
                    'pending' => __('Pending'),
                    'paid' => __('Paid'),
                    'cancelled' => __('Cancelled'),
                ])
                ->default('pending')
                ->required(),
            \Filament\Forms\Components\RichEditor::make('notes')
                ->label(__('Notes'))
                ->columnSpanFull()
                ->maxLength(65535),
        ]);
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('base_amount')
                    ->label(__('Base Amount')),
                \Filament\Tables\Columns\TextColumn::make('bonus')
                    ->label(__('Bonus')),
                \Filament\Tables\Columns\TextColumn::make('deductions')
                    ->label(__('Deductions')),
                \Filament\Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('Total Amount')),
                \Filament\Tables\Columns\TextColumn::make('payment_date')
                    ->label(__('Payment Date')),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->colors([
                        'danger' => 'cancelled',
                        'warning' => 'pending',
                        'success' => 'paid',
                    ]),
            ])
            ->filters([
                // Add filters if needed
            ])
            ->headerActions([
                \Filament\Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
