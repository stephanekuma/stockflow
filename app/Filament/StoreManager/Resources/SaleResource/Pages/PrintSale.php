<?php

namespace App\Filament\StoreManager\Resources\SaleResource\Pages;

use Filament\Resources\Pages\Page;
use App\Filament\StoreManager\Resources\SaleResource;

class PrintSale extends Page
{
    protected static string $resource = SaleResource::class;
    protected static string $view = 'filament.store-manager.resources.sale-resource.pages.print-sale';
}
