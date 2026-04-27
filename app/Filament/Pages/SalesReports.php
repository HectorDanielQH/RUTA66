<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class SalesReports extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string | \UnitEnum | null $navigationGroup = 'Reportes';

    protected static ?string $navigationLabel = 'Ventas';

    protected static ?string $title = 'Reportes de ventas';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.sales-reports';

    public string $period = 'day';

    public string $date = '';

    public string $month = '';

    public string $year = '';

    public string $employee = 'all';

    public function mount(): void
    {
        $this->date = today()->format('Y-m-d');
        $this->month = today()->format('Y-m');
        $this->year = today()->format('Y');

        if (! $this->canViewAllSales()) {
            $this->employee = (string) Auth::id();
        }
    }

    public function canViewAllSales(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public function getEmployees(): Collection
    {
        $query = User::query()
            ->orderBy('nombres')
            ->orderBy('username');

        if (! $this->canViewAllSales()) {
            $query->whereKey(Auth::id());
        }

        return $query->get();
    }

    public function getSummary(): array
    {
        $orders = $this->baseOrdersQuery();
        $validOrders = (clone $orders)->where('status', '!=', 'cancelled');

        $ordersCount = (clone $orders)->count();
        $cancelledCount = (clone $orders)->where('status', 'cancelled')->count();
        $salesTotal = (float) (clone $validOrders)->sum('total');
        $deliveryTotal = (float) (clone $validOrders)->sum('delivery_fee');

        return [
            'orders_count' => $ordersCount,
            'cancelled_count' => $cancelledCount,
            'sales_total' => 'Bs ' . Number::format($salesTotal, 2),
            'delivery_total' => 'Bs ' . Number::format($deliveryTotal, 2),
            'average_ticket' => 'Bs ' . Number::format($ordersCount > 0 ? $salesTotal / max($ordersCount - $cancelledCount, 1) : 0, 2),
        ];
    }

    public function getEmployeeSales(): Collection
    {
        return $this->baseOrdersQuery()
            ->where('status', '!=', 'cancelled')
            ->select('user_id', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total) as total_sales'))
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('total_sales')
            ->get();
    }

    public function getProductSales(): Collection
    {
        $orderIds = $this->baseOrdersQuery()
            ->where('status', '!=', 'cancelled')
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            return collect();
        }

        return OrderItem::query()
            ->whereIn('order_id', $orderIds)
            ->select('product_name', DB::raw('SUM(quantity) as quantity_sold'), DB::raw('SUM(subtotal) as total_sales'))
            ->groupBy('product_name')
            ->orderByDesc('quantity_sold')
            ->limit(10)
            ->get();
    }

    protected function baseOrdersQuery(): Builder
    {
        $query = Order::query()->with('user');

        match ($this->period) {
            'month' => $query->whereBetween('created_at', [
                Carbon::createFromFormat('Y-m', $this->month)->startOfMonth(),
                Carbon::createFromFormat('Y-m', $this->month)->endOfMonth(),
            ]),
            'year' => $query->whereBetween('created_at', [
                Carbon::createFromFormat('Y', $this->year)->startOfYear(),
                Carbon::createFromFormat('Y', $this->year)->endOfYear(),
            ]),
            default => $query->whereDate('created_at', $this->date),
        };

        if (! $this->canViewAllSales()) {
            $query->where('user_id', Auth::id());
        } elseif ($this->employee !== 'all') {
            $query->where('user_id', $this->employee === 'none' ? null : (int) $this->employee);
        }

        return $query;
    }
}
