<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;

class OrderTicketController extends Controller
{
    public function __invoke(Order $order): View
    {
        $order->load(['cashRegister', 'customer', 'deliveryZone', 'items', 'user']);
        $user = auth()->user();

        abort_unless(
            $user?->hasRole('super_admin')
                || $order->user_id === $user?->getKey()
                || $order->cashRegister?->user_id === $user?->getKey(),
            403
        );

        return view('orders.ticket', [
            'order' => $order,
        ]);
    }
}
