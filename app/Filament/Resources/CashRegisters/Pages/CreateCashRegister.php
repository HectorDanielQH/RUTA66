<?php

namespace App\Filament\Resources\CashRegisters\Pages;

use App\Filament\Resources\CashRegisters\CashRegisterResource;
use App\Models\CashRegister;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateCashRegister extends CreateRecord
{
    protected static string $resource = CashRegisterResource::class;

    protected static ?string $title = 'Abrir turno de caja';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $hasOpenRegister = CashRegister::query()
            ->where('user_id', Auth::id())
            ->where('status', 'open')
            ->exists();

        if ($hasOpenRegister) {
            Notification::make()
                ->title('Ya tienes una caja abierta')
                ->body('Cierra la caja actual antes de abrir una nueva.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'user_id' => 'Ya existe una caja abierta para este usuario.',
            ]);
        }

        $data['user_id'] = Auth::id();
        $data['code'] = 'CAJA-' . now()->format('YmdHis');
        $data['status'] = 'open';
        $data['expected_amount'] = 0;
        $data['difference_amount'] = 0;
        $data['opened_at'] = $data['opened_at'] ?? now();

        return $data;
    }
}
