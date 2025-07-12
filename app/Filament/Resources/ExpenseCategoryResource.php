<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseCategoryResource\Pages;
use App\Filament\Resources\ExpenseCategoryResource\RelationManagers;
use App\Models\ExpenseCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseCategoryResource extends Resource
{
    protected static ?string $model = ExpenseCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?int $navigationSort = 51;

    public static function getNavigationGroup(): string
    {
        return __('Expense Management');
    }

    public static function getModelLabel(): string
    {
        return __('Expense Category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Expense Categories');
    }

    public static function form(Form $form): Form
    {
        return $form->schema(
            static::getFormSchema(),
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('Name')),
                Tables\Columns\TextColumn::make('description')->label(__('Description'))->limit(30),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListExpenseCategories::route('/'),
            // 'create' => Pages\CreateExpenseCategory::route('/create'),
            // 'edit' => Pages\EditExpenseCategory::route('/{record}/edit'),
        ];
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('Name'))
                        ->required(),
                    Forms\Components\Textarea::make('description')
                        ->label(__('Description'))
                        ->nullable(),
                    Forms\Components\Select::make('type')
                        ->label(__('Type'))
                        ->options([
                            'general' => __('General'),
                            'employee' => __('Employee Related'),
                            'utility' => __('Utility'),
                            'maintenance' => __('Maintenance'),
                            'other' => __('Other'),
                        ])
                        ->default('general')
                        ->required(),
                ])
        ];
    }
}
