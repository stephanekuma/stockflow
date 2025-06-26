<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Setting;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function __invoke(Purchase $purchase)
    {
        $purchase->load(['purchasedProducts.productUnit.product', 'purchasedProducts.productUnit.unit', 'provider', 'store']);
        $settings = Setting::first();

        return view('purchases.print', compact('purchase', 'settings'));
    }
}
