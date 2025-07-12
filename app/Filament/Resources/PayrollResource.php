<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollResource\Pages;
use App\Models\Payroll;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('HR Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Payroll Information'))
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label(__('Employee'))
                            ->relationship('employee', 'first_name', function (Builder $query) {
                                return $query->where('store_id', Filament::getTenant()->id);
                            })
                            ->searchable(['first_name', 'last_name'])
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $employee = \App\Models\Employee::find($state);
                                    if ($employee) {
                                        $set('base_amount', $employee->base_salary);
                                        $bonus = (float) $get('bonus') ?: 0;
                                        $deductions = (float) $get('deductions') ?: 0;
                                        $base = (float) $employee->base_salary ?: 0;
                                        $set('total_amount', $base + $bonus - $deductions);
                                    }
                                } else {
                                    $set('base_amount', null);
                                    $set('total_amount', null);
                                }
                            }),
                        Forms\Components\TextInput::make('base_amount')
                            ->label(__('Base Amount'))
                            ->required()
                            ->numeric()
                            ->prefix('XOF')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $bonus = (float) $get('bonus') ?: 0;
                                $deductions = (float) $get('deductions') ?: 0;
                                $base = (float) $state ?: 0;
                                $set('total_amount', $base + $bonus - $deductions);
                            }),
                        Forms\Components\TextInput::make('bonus')
                            ->label(__('Bonus'))
                            ->numeric()
                            ->default(0)
                            ->prefix('XOF')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $base = (float) $get('base_amount') ?: 0;
                                $deductions = (float) $get('deductions') ?: 0;
                                $bonus = (float) $state ?: 0;
                                $set('total_amount', $base + $bonus - $deductions);
                            }),
                        Forms\Components\TextInput::make('deductions')
                            ->label(__('Deductions'))
                            ->numeric()
                            ->default(0)
                            ->prefix('XOF')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $base = (float) $get('base_amount') ?: 0;
                                $bonus = (float) $get('bonus') ?: 0;
                                $deductions = (float) $state ?: 0;
                                $set('total_amount', $base + $bonus - $deductions);
                            }),
                        Forms\Components\TextInput::make('total_amount')
                            ->label(__('Total Amount'))
                            ->numeric()
                            ->prefix('XOF')
                            ->disabled()
                            ->dehydrated(false)
                            ->reactive(),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label(__('Payment Date'))
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->label(__('Payment Method'))
                            ->native(false)
                            ->options([
                                'cash' => __('Cash'),
                                'bank_transfer' => __('Bank Transfer'),
                                'check' => __('Check'),
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label(__('Status'))
                            ->native(false)
                            ->options([
                                'pending' => __('Pending'),
                                'paid' => __('Paid'),
                                'cancelled' => __('Cancelled'),
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\RichEditor::make('notes')
                            ->label(__('Notes'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label(__('Employee'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('Total Amount'))
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label(__('Payment Date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('Payment Method')),
                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('Status'))
                    ->colors([
                        'danger' => 'cancelled',
                        'warning' => 'pending',
                        'success' => 'paid',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => __('Pending'),
                        'paid' => __('Paid'),
                        'cancelled' => __('Cancelled'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrolls::route('/'),
            'create' => Pages\CreatePayroll::route('/create'),
            'edit' => Pages\EditPayroll::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\PayrollResource\RelationManagers\EmployeeRelationManager::class,
        ];
    }
}
