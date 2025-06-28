<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Livewire\UnitConversionTester;

class UnitConversionPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static string $view = 'filament.pages.unit-conversion-page';

    protected static ?string $title = 'Test de Conversion d\'Unités';

    protected static ?string $navigationGroup = 'Outils';

    protected static ?int $navigationSort = 100;

    public function getTitle(): string
    {
        return 'Test de Conversion d\'Unités';
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }
}
