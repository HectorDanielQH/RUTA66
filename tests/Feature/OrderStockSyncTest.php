<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStockSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_saved_items_uses_persisted_items_when_updating_stock(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Burgers',
            'slug' => 'burgers',
            'is_active' => true,
        ]);
        $customer = Customer::query()->create([
            'name' => 'Cliente Demo',
            'phone' => '70000000',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Producto Demo',
            'slug' => 'producto-demo',
            'price' => 10,
            'stock' => 10,
            'is_available' => true,
            'is_featured' => false,
        ]);
        $cashRegister = CashRegister::query()->create([
            'user_id' => $user->id,
            'code' => 'CJ-TEST-01',
            'status' => 'open',
            'opening_amount' => 0,
            'expected_amount' => 0,
            'difference_amount' => 0,
            'opened_at' => now(),
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'cash_register_id' => $cashRegister->id,
            'order_number' => 'ORD-TEST-001',
            'order_type' => 'local',
            'status' => 'pending',
            'payment_method' => 'cash',
            'subtotal' => 20,
            'delivery_fee' => 0,
            'total' => 20,
            'stock_applied' => true,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 10,
            'subtotal' => 20,
        ]);

        $product->update(['stock' => 8]);

        $originalQuantities = [$product->id => 2];

        $order->items()->update(['quantity' => 4, 'subtotal' => 40]);

        $order->syncSavedItemsToStock($originalQuantities);

        $this->assertSame(6, $product->fresh()->stock);
    }

    public function test_cancelled_order_restores_stock_when_syncing_saved_items(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Bebidas',
            'slug' => 'bebidas',
            'is_active' => true,
        ]);
        $customer = Customer::query()->create([
            'name' => 'Cliente Demo',
            'phone' => '70000000',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Bebida Demo',
            'slug' => 'bebida-demo',
            'price' => 5,
            'stock' => 7,
            'is_available' => true,
            'is_featured' => false,
        ]);
        $cashRegister = CashRegister::query()->create([
            'user_id' => $user->id,
            'code' => 'CJ-TEST-02',
            'status' => 'open',
            'opening_amount' => 0,
            'expected_amount' => 0,
            'difference_amount' => 0,
            'opened_at' => now(),
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'cash_register_id' => $cashRegister->id,
            'order_number' => 'ORD-TEST-002',
            'order_type' => 'local',
            'status' => 'cancelled',
            'payment_method' => 'cash',
            'subtotal' => 15,
            'delivery_fee' => 0,
            'total' => 15,
            'stock_applied' => true,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 3,
            'unit_price' => 5,
            'subtotal' => 15,
        ]);

        $order->syncSavedItemsToStock([$product->id => 3]);

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertFalse((bool) $order->fresh()->stock_applied);
    }
}
