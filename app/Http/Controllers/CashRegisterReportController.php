<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use Illuminate\Contracts\View\View;

class CashRegisterReportController extends Controller
{
    public function __invoke(CashRegister $cashRegister): View
    {
        $user = auth()->user();

        abort_unless(
            $user?->hasRole('super_admin') || $cashRegister->user_id === $user?->getKey(),
            403
        );

        $cashRegister->load([
            'user',
            'orders' => fn ($query) => $query->with('customer')->latest(),
        ]);

        return view('cash-registers.report', [
            'cashRegister' => $cashRegister,
        ]);
    }
}
