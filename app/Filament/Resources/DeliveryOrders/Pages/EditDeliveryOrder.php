<?php

namespace App\Filament\Resources\DeliveryOrders\Pages;

use App\Filament\Resources\DeliveryOrders\DeliveryOrderResource;
use App\Filament\Resources\Orders\Pages\EditOrder;

class EditDeliveryOrder extends EditOrder
{
    protected static string $resource = DeliveryOrderResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['order_type'] = 'delivery';

        return parent::mutateFormDataBeforeSave($data);
    }
}
