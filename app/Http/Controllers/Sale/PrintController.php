<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function __invoke(Sale $sale)
    {
        $sale->load(['customer', 'store', 'soldProducts.productUnit.product', 'soldProducts.productUnit.unit']);
        $settings = Setting::first();

        return view('sales.print', compact('sale', 'settings'));
    }
}
