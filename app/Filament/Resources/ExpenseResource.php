<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Resources\ExpenseResource\RelationManagers;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\CashRegisterService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\DateFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): string
    {
        return __('Expense Management');
    }

    public static function getModelLabel(): string
    {
        return __('Expense');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Expenses');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\DatePicker::make('date')
                        ->label(__('Date'))
                        ->required(),
                    Forms\Components\TextInput::make('amount')
                        ->label(__('Amount'))
                        ->numeric()
                        ->prefix('XOF')
                        ->required(),
                    Forms\Components\Select::make('expense_category_id')
                        ->label(__('Category'))
                        ->preload()
                        ->options(ExpenseCategory::query()->where('store_id', Filament::getTenant()->id)->get()->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->createOptionForm(array_merge(
                            ExpenseCategoryResource::getFormSchema(),
                            [
                                Forms\Components\Hidden::make('store_id')
                                    ->default(Filament::getTenant()->id),
                            ]
                        ))
                        ->createOptionModalHeading(__('Create Expense Category')),
                    Forms\Components\Select::make('user_id')
                        ->label(__('User'))
                        ->options(User::query()->pluck('name', 'id'))
                        ->preload()
                        ->searchable()
                        ->nullable(),
                    Forms\Components\Textarea::make('description')
                        ->label(__('Description'))
                        ->rows(2)
                        ->columnSpanFull()
                        ->nullable()
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label(__('Date'))
                    ->date(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money('XOF'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('Category')),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('User')),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('Description'))
                    ->limit(30),
            ])
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->label(__('Date'))
                    ->form([
                        Forms\Components\DatePicker::make('date'),
                    ]),
                SelectFilter::make('expense_category_id')
                    ->label(__('Category'))
                    ->options(ExpenseCategory::pluck('name', 'id')),
                SelectFilter::make('user_id')
                    ->label(__('User'))
                    ->options(User::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
