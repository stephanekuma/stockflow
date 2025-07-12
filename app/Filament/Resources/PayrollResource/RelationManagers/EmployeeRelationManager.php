<?php

namespace App\Filament\Resources\PayrollResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EmployeeRelationManager extends RelationManager
{
    protected static string $relationship = 'employee';
    protected static ?string $recordTitleAttribute = 'full_name';

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([
            \Filament\Forms\Components\TextInput::make('first_name')
                ->label('First Name')
                ->required(),
            \Filament\Forms\Components\TextInput::make('last_name')
                ->label('Last Name')
                ->required(),
            \Filament\Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email(),
            \Filament\Forms\Components\TextInput::make('phone')
                ->label('Phone'),
            \Filament\Forms\Components\TextInput::make('position')
                ->label('Position'),
            \Filament\Forms\Components\TextInput::make('base_salary')
                ->label('Base Salary')
                ->numeric(),
            \Filament\Forms\Components\DatePicker::make('hire_date')
                ->label('Hire Date'),
            \Filament\Forms\Components\Toggle::make('is_active')
                ->label('Active'),
        ]);
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('full_name'),
                \Filament\Tables\Columns\TextColumn::make('position'),
                \Filament\Tables\Columns\TextColumn::make('base_salary'),
            ]);
    }
}
