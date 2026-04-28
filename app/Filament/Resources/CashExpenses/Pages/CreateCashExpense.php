<?php

namespace App\Filament\Resources\CashExpenses\Pages;

use App\Filament\Resources\CashExpenses\CashExpenseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCashExpense extends CreateRecord
{
    protected static string $resource = CashExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }
}
