<?php

namespace App\Filament\Resources\CashRegisters\Pages;

use App\Filament\Resources\CashRegisters\CashRegisterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashRegisters extends ListRecords
{
    protected static string $resource = CashRegisterResource::class;

    protected ?string $subheading = 'Abre caja antes de vender. Los pedidos se suman automaticamente y al cerrar el turno el sistema calcula si falta o sobra dinero.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Abrir turno de caja')
                ->icon('heroicon-o-lock-open'),
        ];
    }
}
