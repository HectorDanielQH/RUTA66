<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected array $originalQuantities = [];

    protected array $normalizedItems = [];

    protected ?string $originalStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->originalQuantities = $this->record->items()
            ->selectRaw('product_id, SUM(quantity) as quantity')
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->pluck('quantity', 'product_id')
            ->map(fn ($quantity): int => (int) $quantity)
            ->all();
        $this->originalStatus = $this->record->status;

        [$subtotal, $items] = static::normalizeOrderItems($data['items'] ?? []);
        static::validateStockAvailability($items, $this->originalQuantities);

        $this->normalizedItems = $items;

        $data['items'] = $items;
        $data['subtotal'] = $subtotal;
        $data['delivery_fee'] = (float) ($data['delivery_fee'] ?? 0);
        $data['total'] = $subtotal + $data['delivery_fee'];

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->refresh();
        $subtotal = $this->record->items()->sum('subtotal');

        $this->record->update([
            'subtotal' => $subtotal,
            'total' => $subtotal + (float) $this->record->delivery_fee,
        ]);

        $this->record->syncItemsToStock($this->normalizedItems, $this->originalQuantities);
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

    protected static function validateStockAvailability(array $items, array $reservedQuantities = []): void
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
                Notification::make()
                    ->title('Stock insuficiente')
                    ->body("{$product->name} solo tiene {$availableQuantity} unidades disponibles para este pedido.")
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'items' => "{$product->name} no tiene stock suficiente. Disponible: {$availableQuantity}.",
                ]);
            }
        }

        Order::assertStockAvailability($items, $reservedQuantities);
    }

}
