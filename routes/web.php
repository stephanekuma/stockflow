<?php

use App\Http\Controllers\Purchase;
use App\Http\Controllers\Sale;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/store');

Route::get('/print-purchase/{purchase}', Purchase\PrintController::class)->name('purchase.print');
Route::get('/print-sale/{sale}', Sale\PrintController::class)->name('sales.print');
