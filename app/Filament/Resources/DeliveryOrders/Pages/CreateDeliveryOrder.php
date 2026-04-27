<?php

namespace App\Filament\Resources\DeliveryOrders\Pages;

use App\Filament\Resources\DeliveryOrders\DeliveryOrderResource;
use App\Filament\Resources\Orders\Pages\CreateOrder;

class CreateDeliveryOrder extends CreateOrder
{
    protected static string $resource = DeliveryOrderResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            ...($this->data ?? []),
            'order_type' => 'delivery',
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['order_type'] = 'delivery';

        return parent::mutateFormDataBeforeCreate($data);
    }
}
