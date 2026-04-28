<?php

namespace App\Filament\Resources\CashExpenses\Pages;

use App\Filament\Resources\CashExpenses\CashExpenseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCashExpense extends EditRecord
{
    protected static string $resource = CashExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
