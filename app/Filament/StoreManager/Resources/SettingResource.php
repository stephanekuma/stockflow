<?php

namespace App\Filament\StoreManager\Resources;

use App\Filament\StoreManager\Resources\SettingResource\Pages;
use App\Filament\StoreManager\Resources\SettingResource\RelationManagers;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make("key")
                            ->label(__("Key"))
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make("type")
                            ->native(false)
                            ->label(__("Field Type"))
                            ->searchable()
                            ->options([
                                "text" => "Text Input",
                                "boolean" => "Boolean",
                                "select" => "Select",
                                "file" => "File"
                            ])
                            ->reactive(),

                        Forms\Components\TextInput::make("group")
                            ->label(__("Group"))
                            ->datalist(Setting::pluck('group')->toArray()),

                        Forms\Components\Repeater::make("attributes.options")
                            ->label(__("default.Options"))
                            ->grid(2)
                            ->simple(
                                Forms\Components\TextInput::make('key')
                                    ->required(),
                            )
                            ->visible(function (callable $get) {
                                return $get('type') == "select";
                            })
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label(__('Key'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('group')
                    ->label(__('Group'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Field Type'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
