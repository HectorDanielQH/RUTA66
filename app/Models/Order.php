<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'cash_register_id',
        'delivery_zone_id',
        'order_number',
        'order_type',
        'status',
        'payment_method',
        'phone',
        'delivery_address',
        'delivery_reference',
        'latitude',
        'longitude',
        'estimated_delivery_time_minutes',
        'notes',
        'subtotal',
        'delivery_fee',
        'total',
        'stock_applied',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'estimated_delivery_time_minutes' => 'integer',
            'total' => 'decimal:2',
            'stock_applied' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function restoreItemsToStock(): void
    {
        if (! $this->stock_applied) {
            return;
        }

        $this->items()
            ->whereNotNull('product_id')
            ->get()
            ->groupBy('product_id')
            ->each(function ($items, int $productId): void {
                Product::whereKey($productId)->increment('stock', (int) $items->sum('quantity'));
            });

        $this->forceFill(['stock_applied' => false])->save();
    }

    public function applyItemsToStock(array $items): void
    {
        if ($this->stock_applied || $this->status === 'cancelled') {
            return;
        }

        DB::transaction(function () use ($items): void {
            collect($items)
                ->filter(fn (array $item): bool => filled($item['product_id'] ?? null))
                ->groupBy('product_id')
                ->each(function ($items, int $productId): void {
                    $requestedQuantity = (int) collect($items)->sum('quantity');
                    $product = Product::query()->whereKey($productId)->lockForUpdate()->first();

                    if (! $product) {
                        return;
                    }

                    if ($requestedQuantity > $product->stock) {
                        throw ValidationException::withMessages([
                            'items' => "{$product->name} no tiene stock suficiente. Disponible: {$product->stock}.",
                        ]);
                    }

                    $product->decrement('stock', $requestedQuantity);
                });

            $this->forceFill(['stock_applied' => true])->save();
        });
    }

    public static function assertStockAvailability(array $items, array $reservedQuantities = []): void
    {
        $requestedByProduct = collect($items)
            ->filter(fn (array $item): bool => filled($item['product_id'] ?? null))
            ->groupBy('product_id')
            ->map(fn ($items): int => (int) collect($items)->sum('quantity'));

        foreach ($requestedByProduct as $productId => $requestedQuantity) {
            $product = Product::find($productId);

            if (! $product) {
                continue;
            }

            $availableQuantity = $product->stock + (int) ($reservedQuantities[$productId] ?? 0);

            if ($requestedQuantity > $availableQuantity) {
                throw ValidationException::withMessages([
                    'items' => "{$product->name} no tiene stock suficiente. Disponible: {$availableQuantity}.",
                ]);
            }
        }
    }

    public function applySavedItemsToStock(): void
    {
        $items = $this->items()
            ->whereNotNull('product_id')
            ->get(['product_id', 'quantity'])
            ->map(fn (OrderItem $item): array => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ])
            ->all();

        $this->applyItemsToStock($items);
    }

    public function syncItemsToStock(array $newItems, array $originalQuantities): void
    {
        if ($this->status === 'cancelled') {
            $this->restoreItemsToStock();

            return;
        }

        if (! $this->stock_applied) {
            $this->applyItemsToStock($newItems);

            return;
        }

        $newQuantities = collect($newItems)
            ->filter(fn (array $item): bool => filled($item['product_id'] ?? null))
            ->groupBy('product_id')
            ->map(fn ($items): int => (int) collect($items)->sum('quantity'))
            ->all();

        $productIds = collect(array_keys($originalQuantities))
            ->merge(array_keys($newQuantities))
            ->unique();

        DB::transaction(function () use ($productIds, $newQuantities, $originalQuantities): void {
            foreach ($productIds as $productId) {
                $difference = (int) ($newQuantities[$productId] ?? 0) - (int) ($originalQuantities[$productId] ?? 0);

                if ($difference > 0) {
                    $product = Product::query()->whereKey($productId)->lockForUpdate()->first();

                    if (! $product) {
                        continue;
                    }

                    if ($difference > $product->stock) {
                        throw ValidationException::withMessages([
                            'items' => "{$product->name} no tiene stock suficiente. Disponible: {$product->stock}.",
                        ]);
                    }

                    $product->decrement('stock', $difference);
                }

                if ($difference < 0) {
                    Product::whereKey($productId)->increment('stock', abs($difference));
                }
            }
        });
    }
}
