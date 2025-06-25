<?php

use App\Models\Unit;
use App\Models\Brand;
use App\Models\Product;
use Livewire\Volt\Volt;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/store');

Route::get('/test-product-creation', function () {
    Log::info('Attempting to create a product from web route.');

    // Ensure you have a Category, Brand, and Store to link to
    $category = Category::firstOrCreate(['name' => 'Test Category', 'store_id' => 1]); // Adjust store_id as needed
    $brand = Brand::firstOrCreate(['name' => 'Test Brand', 'store_id' => 1]); // Adjust store_id as needed

    try {
        $product = Product::create([
            'store_id' => 1, // Use a valid store ID
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Test Product ' . uniqid(),
            'sku' => 'TEST-' . uniqid(),
            'description' => 'A test product.',
            'image' => null,
            'data' => ['color' => 'red'],
        ]);

        // Manually simulate `units_data`
        $unit = Unit::firstOrCreate(['name' => 'Piece', 'store_id' => 1]); // Adjust store_id as needed
        $unitsData = [
            [
                'unit_id' => $unit->id,
                'quantity' => 10,
                'cost_price' => 5.00,
                'price' => 10.00,
            ],
        ];

        // To trigger the syncUnits method, we need to set the 'units_data' attribute on the product
        // or directly call the sync method if it were public.
        // For testing the 'booted' method, we'll rely on it receiving data via request input in Filament,
        // but here we just want to see if the Product::create itself logs.
        // If you want to test syncUnits explicitly, you'd need to make it public for this test route or create a dummy request.

        Log::info('Product created: ' . $product->id);
        return 'Product created successfully. Check logs.';
    } catch (\Exception $e) {
        Log::error('Error creating product: ' . $e->getMessage());
        return 'Error creating product: ' . $e->getMessage();
    }
});

// Route::get('/', function () {
//     return view('welcome');
// })->name('home');

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

// Route::middleware(['auth'])->group(function () {
//     Route::redirect('settings', 'settings/profile');

//     Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
//     Volt::route('settings/password', 'settings.password')->name('settings.password');
//     Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
// });

// require __DIR__.'/auth.php';
