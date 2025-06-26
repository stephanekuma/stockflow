<?php

use App\Http\Controllers\Purchase;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/store');

Route::get('/print-purchase/{purchase}', Purchase\PrintController::class)->name('purchase.print');
