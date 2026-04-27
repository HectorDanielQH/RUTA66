<?php

use App\Http\Controllers\OrderTicketController;
use App\Http\Controllers\CashRegisterReportController;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $whatsappNumber = preg_replace('/\D+/', '', env('RUTA66_WHATSAPP', '70000000'));
    $whatsappForUrl = str_starts_with($whatsappNumber, '591') ? $whatsappNumber : "591{$whatsappNumber}";
    $whatsappDisplay = trim(chunk_split($whatsappNumber, 3, ' '));

    $categories = Category::query()
        ->where('is_active', true)
        ->with([
            'products' => fn ($query) => $query
                ->where('is_available', true)
                ->orderBy('name'),
        ])
        ->orderBy('name')
        ->get()
        ->filter(fn (Category $category): bool => $category->products->isNotEmpty());

    $featuredProducts = Product::query()
        ->where('is_available', true)
        ->where('is_featured', true)
        ->orderBy('name')
        ->limit(6)
        ->get();

    $deliveryZones = DeliveryZone::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    return view('welcome', [
        'categories' => $categories,
        'featuredProducts' => $featuredProducts,
        'deliveryZones' => $deliveryZones,
        'whatsappDisplay' => $whatsappDisplay,
        'whatsappUrl' => "https://wa.me/{$whatsappForUrl}",
    ]);
});

Route::middleware('auth')->get('/admin/orders/{order}/ticket', OrderTicketController::class)
    ->name('orders.ticket');

Route::middleware('auth')->get('/admin/cash-registers/{cashRegister}/report', CashRegisterReportController::class)
    ->name('cash-registers.report');
