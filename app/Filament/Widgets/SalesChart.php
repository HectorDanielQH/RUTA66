<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SalesChart extends ChartWidget
{
    protected ?string $heading = 'Ventas de los ultimos 7 dias';

    protected ?string $description = 'Total vendido por dia, excluyendo pedidos cancelados.';

    protected string $color = 'success';

    protected function ordersQuery(): Builder
    {
        $query = Order::query();
        $user = Auth::user();

        if ($user && ! $user->hasRole('super_admin')) {
            $query->where('user_id', $user->getKey());
        }

        return $query;
    }

    protected function getData(): array
    {
        $days = collect(range(6, 0))
            ->map(fn (int $daysAgo): Carbon => today()->subDays($daysAgo))
            ->push(today());

        $sales = $days->map(function (Carbon $day): float {
            return (float) $this->ordersQuery()
                ->whereDate('created_at', $day)
                ->whereNotIn('status', ['cancelled'])
                ->sum('total');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Ventas',
                    'data' => $sales->all(),
                ],
            ],
            'labels' => $days
                ->map(fn (Carbon $day): string => $day->format('d/m'))
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
