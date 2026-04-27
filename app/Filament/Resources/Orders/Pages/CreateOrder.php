<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\Product;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected array $normalizedItems = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['order_number'] = 'ORD-' . now()->format('YmdHis');
        $data['user_id'] = Auth::id();
        $cashRegisterId = CashRegister::query()
            ->where('user_id', Auth::id())
            ->where('status', 'open')
            ->latest('opened_at')
            ->value('id');

        if (! $cashRegisterId) {
            Notification::make()
                ->title('No tienes caja abierta')
                ->body('Abre una caja antes de registrar pedidos para que el arqueo cuadre.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'cash_register_id' => 'Debes abrir una caja antes de crear pedidos.',
            ]);
        }

        $data['cash_register_id'] = $cashRegisterId;

        [$subtotal, $items] = static::normalizeOrderItems($data['items'] ?? []);
        static::validateStockAvailability($items);

        $this->normalizedItems = $items;

        $data['items'] = $items;
        $data['subtotal'] = $subtotal;
        $data['delivery_fee'] = (float) ($data['delivery_fee'] ?? 0);
        $data['total'] = $subtotal + $data['delivery_fee'];

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->refresh();
        $this->record->update([
            'subtotal' => $this->record->items()->sum('subtotal'),
            'total' => $this->record->items()->sum('subtotal') + (float) $this->record->delivery_fee,
        ]);
        $this->record->applySavedItemsToStock();
    }

    protected static function normalizeOrderItems(array $items): array
    {
        $subtotal = 0;

        $normalized = collect($items)->map(function (array $item) use (&$subtotal): array {
            $product = isset($item['product_id']) ? Product::find($item['product_id']) : null;
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = (float) ($item['unit_price'] ?? $product?->price ?? 0);
            $lineSubtotal = $quantity * $unitPrice;
            $subtotal += $lineSubtotal;

            return [
                'product_id' => $product?->id,
                'product_name' => $product?->name ?? ($item['product_name'] ?? ''),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $lineSubtotal,
                'notes' => $item['notes'] ?? null,
            ];
        })->all();

        return [$subtotal, $normalized];
    }

    protected static function validateStockAvailability(array $items): void
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

            if ($requestedQuantity > $product->stock) {
                Notification::make()
                    ->title('Stock insuficiente')
                    ->body("{$product->name} solo tiene {$product->stock} unidades disponibles.")
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'items' => "{$product->name} no tiene stock suficiente. Disponible: {$product->stock}.",
                ]);
            }
        }

        Order::assertStockAvailability($items);
    }

}
