<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class RestaurantStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Resumen de Ruta 66';

    protected ?string $description = 'Indicadores principales segun tu usuario.';

    protected function ordersQuery(): Builder
    {
        $query = Order::query();
        $user = Auth::user();

        if ($user && ! $user->hasRole('super_admin')) {
            $query->where('user_id', $user->getKey());
        }

        return $query;
    }

    protected function getStats(): array
    {
        $todayOrders = $this->ordersQuery()
            ->whereDate('created_at', today())
            ->count();

        $todaySales = $this->ordersQuery()
            ->whereDate('created_at', today())
            ->where('status', 'delivered')
            ->sum('total');

        $pendingOrders = $this->ordersQuery()
            ->where('status', 'preparing')
            ->count();

        $availableProducts = Product::query()
            ->where('is_available', true)
            ->count();

        return [
            Stat::make('Pedidos de hoy', $todayOrders)
                ->description('Pedidos registrados hoy')
                ->color('primary')
                ->icon('heroicon-o-shopping-bag'),
            Stat::make('Ventas de hoy', 'Bs ' . Number::format((float) $todaySales, 2))
                ->description('No incluye pedidos cancelados')
                ->color('success')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Pedidos activos', $pendingOrders)
                ->description('Actualmente en preparacion')
                ->color('warning')
                ->icon('heroicon-o-clock'),
            Stat::make('Productos disponibles', $availableProducts)
                ->description('Activos para venta')
                ->color('info')
                ->icon('heroicon-o-archive-box'),
            Stat::make('Clientes', Customer::query()->count())
                ->description('Clientes registrados')
                ->color('gray')
                ->icon('heroicon-o-users'),
            Stat::make('Zonas delivery', DeliveryZone::query()->where('is_active', true)->count())
                ->description('Zonas activas')
                ->color('danger')
                ->icon('heroicon-o-map'),
        ];
    }
}
