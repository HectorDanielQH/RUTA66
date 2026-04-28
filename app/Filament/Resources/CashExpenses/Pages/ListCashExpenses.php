<?php

namespace App\Filament\Resources\CashExpenses\Pages;

use App\Filament\Resources\CashExpenses\CashExpenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashExpenses extends ListRecords
{
    protected static string $resource = CashExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
