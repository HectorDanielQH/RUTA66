<x-filament-panels::page>
    @php
        $summary = $this->getSummary();
        $employeeSales = $this->getEmployeeSales();
        $productSales = $this->getProductSales();
        $employees = $this->getEmployees();
        $canViewAllSales = $this->canViewAllSales();
        $salesReportsCss = 'css/sales-reports.css';
    @endphp

    <link rel="stylesheet" href="{{ asset($salesReportsCss) }}?v={{ filemtime(public_path($salesReportsCss)) }}">

    <style>
        .sales-report {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .sales-report__hero,
        .sales-report__card,
        .sales-report__table-card {
            border: 1px solid #e7e5e4;
            border-radius: 1.25rem;
            background: #fff;
            box-shadow: 0 10px 24px rgba(28, 25, 23, 0.06);
        }

        .sales-report__hero {
            overflow: hidden;
            border-color: #fde68a;
            background: linear-gradient(135deg, #fffbeb 0%, #fff 48%, #fafaf9 100%);
        }

        .sales-report__hero-head {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #fde68a;
        }

        .sales-report__eyebrow {
            margin: 0;
            color: #b45309;
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .sales-report__title {
            margin: .25rem 0 0;
            color: #1c1917;
            font-size: 1.5rem;
            font-weight: 850;
            line-height: 1.15;
        }

        .sales-report__subtitle {
            margin: .35rem 0 0;
            color: #57534e;
            font-size: .92rem;
        }

        .sales-report__filters {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
            padding: 1.25rem 1.5rem 1.5rem;
        }

        .sales-report__field--wide {
            grid-column: span 2;
        }

        .sales-report__label {
            display: block;
            margin-bottom: .45rem;
            color: #44403c;
            font-size: .86rem;
            font-weight: 750;
        }

        .sales-report__input {
            width: 100%;
            border: 1px solid #d6d3d1;
            border-radius: .8rem;
            background: #fff;
            padding: .58rem .75rem;
            color: #292524;
            font-size: .92rem;
            box-shadow: 0 1px 2px rgba(28, 25, 23, .04);
        }

        .sales-report__input:focus {
            border-color: #f59e0b;
            outline: 2px solid rgba(245, 158, 11, .18);
        }

        .sales-report__stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 1rem;
        }

        .sales-report__card {
            padding: 1.25rem;
        }

        .sales-report__card--sales {
            border-color: #a7f3d0;
            background: #ecfdf5;
        }

        .sales-report__card--delivery {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .sales-report__card--average {
            border-color: #bae6fd;
            background: #f0f9ff;
        }

        .sales-report__card--cancelled {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .sales-report__card-label {
            margin: 0;
            color: #78716c;
            font-size: .72rem;
            font-weight: 850;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .sales-report__card-value {
            margin: .65rem 0 .15rem;
            color: #1c1917;
            font-size: 1.8rem;
            font-weight: 900;
            line-height: 1;
        }

        .sales-report__card-help {
            margin: 0;
            color: #78716c;
            font-size: .78rem;
        }

        .sales-report__card--sales .sales-report__card-value,
        .sales-report__card--sales .sales-report__card-help {
            color: #047857;
        }

        .sales-report__card--delivery .sales-report__card-value,
        .sales-report__card--delivery .sales-report__card-help {
            color: #b45309;
        }

        .sales-report__card--average .sales-report__card-value,
        .sales-report__card--average .sales-report__card-help {
            color: #0369a1;
        }

        .sales-report__card--cancelled .sales-report__card-value,
        .sales-report__card--cancelled .sales-report__card-help {
            color: #b91c1c;
        }

        .sales-report__tables {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
        }

        .sales-report__table-card {
            overflow: hidden;
        }

        .sales-report__table-head {
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid #e7e5e4;
        }

        .sales-report__table-title {
            margin: 0;
            color: #1c1917;
            font-size: 1.05rem;
            font-weight: 850;
        }

        .sales-report__table-subtitle {
            margin: .25rem 0 0;
            color: #78716c;
            font-size: .86rem;
        }

        .sales-report__table-wrap {
            overflow-x: auto;
        }

        .sales-report__table {
            width: 100%;
            border-collapse: collapse;
            font-size: .9rem;
        }

        .sales-report__table thead {
            background: #fafaf9;
        }

        .sales-report__table th {
            padding: .8rem 1rem;
            color: #78716c;
            font-size: .72rem;
            font-weight: 850;
            letter-spacing: .08em;
            text-transform: uppercase;
            text-align: left;
        }

        .sales-report__table td {
            padding: .9rem 1rem;
            border-top: 1px solid #f5f5f4;
            color: #292524;
        }

        .sales-report__table tbody tr:hover {
            background: #fafaf9;
        }

        .sales-report__number {
            text-align: right !important;
        }

        .sales-report__center {
            text-align: center !important;
        }

        .sales-report__pill {
            display: inline-flex;
            min-width: 2rem;
            justify-content: center;
            border-radius: 999px;
            background: #f5f5f4;
            padding: .2rem .65rem;
            color: #57534e;
            font-size: .78rem;
            font-weight: 800;
        }

        .sales-report__pill--amber {
            background: #fef3c7;
            color: #92400e;
        }

        .sales-report__money {
            font-weight: 850;
            color: #047857 !important;
        }

        .sales-report__empty {
            padding: 2rem 1rem !important;
            text-align: center;
            color: #78716c !important;
        }

        @media (max-width: 1100px) {
            .sales-report__stats,
            .sales-report__filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sales-report__tables {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .sales-report__stats,
            .sales-report__filters {
                grid-template-columns: 1fr;
            }

            .sales-report__field--wide {
                grid-column: span 1;
            }
        }
    </style>

    <div class="sales-report">
        <section class="sales-report__hero">
            <div class="sales-report__hero-head">
                <p class="sales-report__eyebrow">Arqueo y ventas</p>
                <h2 class="sales-report__title">Reporte comercial</h2>
                <p class="sales-report__subtitle">
                    @if ($canViewAllSales)
                        Filtra por dia, mes, anio o empleado para revisar cierres y rendimiento.
                    @else
                        Aqui veras solo las ventas registradas por tu usuario.
                    @endif
                </p>
            </div>

            <div class="sales-report__filters">
                <label>
                    <span class="sales-report__label">Periodo</span>
                    <select wire:model.live="period" class="sales-report__input">
                        <option value="day">Por dia</option>
                        <option value="month">Por mes</option>
                        <option value="year">Por anio</option>
                    </select>
                </label>

                @if ($this->period === 'day')
                    <label>
                        <span class="sales-report__label">Dia de arqueo</span>
                        <input type="date" wire:model.live="date" class="sales-report__input">
                    </label>
                @elseif ($this->period === 'month')
                    <label>
                        <span class="sales-report__label">Mes</span>
                        <input type="month" wire:model.live="month" class="sales-report__input">
                    </label>
                @else
                    <label>
                        <span class="sales-report__label">Anio</span>
                        <input type="number" min="2020" max="2100" wire:model.live="year" class="sales-report__input">
                    </label>
                @endif

                @if ($canViewAllSales)
                    <label class="sales-report__field--wide">
                        <span class="sales-report__label">Empleado</span>
                        <select wire:model.live="employee" class="sales-report__input">
                            <option value="all">Todos los empleados</option>
                            <option value="none">Sin empleado asignado</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->getFilamentName() ?: $employee->username }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>
        </section>

        <section class="sales-report__stats">
            <div class="sales-report__card">
                <p class="sales-report__card-label">Pedidos</p>
                <p class="sales-report__card-value">{{ $summary['orders_count'] }}</p>
                <p class="sales-report__card-help">Total de ordenes en el periodo</p>
            </div>
            <div class="sales-report__card sales-report__card--sales">
                <p class="sales-report__card-label">Ventas</p>
                <p class="sales-report__card-value">{{ $summary['sales_total'] }}</p>
                <p class="sales-report__card-help">Sin pedidos cancelados</p>
            </div>
            <div class="sales-report__card sales-report__card--delivery">
                <p class="sales-report__card-label">Delivery</p>
                <p class="sales-report__card-value">{{ $summary['delivery_total'] }}</p>
                <p class="sales-report__card-help">Ingresos por envio</p>
            </div>
            <div class="sales-report__card sales-report__card--average">
                <p class="sales-report__card-label">Ticket promedio</p>
                <p class="sales-report__card-value">{{ $summary['average_ticket'] }}</p>
                <p class="sales-report__card-help">Promedio por pedido valido</p>
            </div>
            <div class="sales-report__card sales-report__card--cancelled">
                <p class="sales-report__card-label">Cancelados</p>
                <p class="sales-report__card-value">{{ $summary['cancelled_count'] }}</p>
                <p class="sales-report__card-help">Pedidos anulados</p>
            </div>
        </section>

        <section class="sales-report__tables">
            <div class="sales-report__table-card">
                <div class="sales-report__table-head">
                    <h2 class="sales-report__table-title">Ventas por empleado</h2>
                    <p class="sales-report__table-subtitle">Ideal para arqueos y control de caja por usuario.</p>
                </div>
                <div class="sales-report__table-wrap">
                    <table class="sales-report__table">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th class="sales-report__center">Pedidos</th>
                                <th class="sales-report__number">Total vendido</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($employeeSales as $sale)
                                <tr>
                                    <td><strong>{{ $sale->user?->getFilamentName() ?: $sale->user?->username ?: 'Sin empleado asignado' }}</strong></td>
                                    <td class="sales-report__center">
                                        <span class="sales-report__pill">{{ $sale->orders_count }}</span>
                                    </td>
                                    <td class="sales-report__number sales-report__money">Bs {{ \Illuminate\Support\Number::format((float) $sale->total_sales, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="sales-report__empty">No hay ventas en este periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sales-report__table-card">
                <div class="sales-report__table-head">
                    <h2 class="sales-report__table-title">Productos mas vendidos</h2>
                    <p class="sales-report__table-subtitle">Ranking del periodo filtrado.</p>
                </div>
                <div class="sales-report__table-wrap">
                    <table class="sales-report__table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="sales-report__center">Cantidad</th>
                                <th class="sales-report__number">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($productSales as $product)
                                <tr>
                                    <td><strong>{{ $product->product_name }}</strong></td>
                                    <td class="sales-report__center">
                                        <span class="sales-report__pill sales-report__pill--amber">{{ $product->quantity_sold }}</span>
                                    </td>
                                    <td class="sales-report__number"><strong>Bs {{ \Illuminate\Support\Number::format((float) $product->total_sales, 2) }}</strong></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="sales-report__empty">No hay productos vendidos en este periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
