<?php

use App\Http\Controllers\Purchase;
use App\Http\Controllers\Sale;
use App\Http\Controllers\UnitConversionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/store');

Route::get('/print-purchase/{purchase}', Purchase\PrintController::class)->name('purchase.print');
Route::get('/print-sale/{sale}', Sale\PrintController::class)->name('sales.print');

// Routes pour la conversion d'unités
Route::prefix('api/unit-conversion')->group(function () {
    Route::post('/selling-strategy', [UnitConversionController::class, 'getSellingStrategy'])->name('unit-conversion.strategy');
    Route::post('/convert', [UnitConversionController::class, 'convertQuantity'])->name('unit-conversion.convert');
    Route::get('/available-units/{product_id}', [UnitConversionController::class, 'getAvailableUnits'])->name('unit-conversion.units');
    Route::post('/check-stock', [UnitConversionController::class, 'checkStock'])->name('unit-conversion.check-stock');
});
