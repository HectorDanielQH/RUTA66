<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Arqueo {{ $cashRegister->code }}</title>
    <style>
        @page {
            margin: 8mm;
            size: 80mm auto;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f5f4;
            color: #111827;
            font-family: "Courier New", monospace;
            font-size: 12px;
        }

        .report {
            width: 80mm;
            min-height: 100vh;
            margin: 0 auto;
            background: #fff;
            padding: 12px;
        }

        .center {
            text-align: center;
        }

        .brand {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .title {
            margin: 4px 0 0;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .section-title {
            margin: 0 0 8px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .muted {
            color: #57534e;
        }

        .divider {
            border-top: 1px dashed #78716c;
            margin: 10px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 4px;
        }

        .strong {
            font-weight: 800;
        }

        .summary-box {
            border: 1px solid #d6d3d1;
            border-radius: 8px;
            background: #fafaf9;
            padding: 8px;
        }

        .summary-box--result {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .summary-box--success {
            background: #ecfdf5;
            border-color: #86efac;
        }

        .summary-box--danger {
            background: #fef2f2;
            border-color: #fca5a5;
        }

        .summary-lead {
            margin: 0 0 6px;
            font-size: 11px;
            line-height: 1.45;
        }

        .pill {
            display: inline-block;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .pill--open {
            background: #dcfce7;
            color: #166534;
        }

        .pill--closed {
            background: #e5e7eb;
            color: #111827;
        }

        .danger {
            color: #b91c1c;
        }

        .success {
            color: #047857;
        }

        .order {
            margin-bottom: 8px;
        }

        .actions {
            position: sticky;
            bottom: 0;
            display: flex;
            gap: 8px;
            justify-content: center;
            margin: 12px -12px -12px;
            padding: 10px;
            background: #fef3c7;
        }

        .button {
            border: 0;
            border-radius: 8px;
            background: #f59e0b;
            color: #111827;
            cursor: pointer;
            font-weight: 800;
            padding: 8px 12px;
        }

        @media print {
            body {
                background: #fff;
            }

            .report {
                width: 100%;
                min-height: auto;
                padding: 0;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    @php
        $openingAmount = (float) $cashRegister->opening_amount;
        $cashSales = (float) $cashRegister->cash_sales_total;
        $qrSales = (float) $cashRegister->qr_sales_total;
        $expensesTotal = (float) $cashRegister->expenses_total;
        $expectedClosing = (float) $cashRegister->expected_closing_amount;
        $actualAmount = (float) $cashRegister->actual_amount;
        $differenceAmount = (float) $cashRegister->difference_amount;
        $salesTotal = (float) $cashRegister->sales_total;
        $differenceLabel = $differenceAmount < 0 ? 'Faltante en caja' : ($differenceAmount > 0 ? 'Sobrante en caja' : 'Caja exacta');
        $differenceClass = $differenceAmount < 0 ? 'danger' : 'success';
        $resultBoxClass = $differenceAmount < 0 ? 'summary-box--danger' : 'summary-box--success';
    @endphp
    <main class="report">
        <header class="center">
            <h1 class="brand">Ruta 66</h1>
            <div class="title">Arqueo de caja</div>
            <div>{{ $cashRegister->code }}</div>
        </header>

        <div class="divider"></div>

        <section>
            <p class="section-title">Resumen del turno</p>
            <div class="row">
                <strong>Atendido por</strong>
                <span>{{ $cashRegister->user?->getFilamentName() ?: $cashRegister->user?->username ?: 'Sin usuario' }}</span>
            </div>
            <div class="row">
                <strong>Apertura</strong>
                <span>{{ $cashRegister->opened_at?->format('d/m/Y H:i') }}</span>
            </div>
            <div class="row">
                <strong>Cierre</strong>
                <span>{{ $cashRegister->closed_at?->format('d/m/Y H:i') ?? 'Caja abierta' }}</span>
            </div>
            <div class="row">
                <strong>Estado</strong>
                <span class="pill {{ $cashRegister->status === 'open' ? 'pill--open' : 'pill--closed' }}">
                    {{ $cashRegister->status === 'open' ? 'Caja abierta' : 'Caja cerrada' }}
                </span>
            </div>
        </section>

        <div class="divider"></div>

        <section>
            <p class="section-title">Dinero del turno</p>

            <div class="summary-box">
                <p class="summary-lead strong">1. Dinero que entro durante el turno</p>
                <div class="row">
                    <span>Efectivo con el que se abrio la caja</span>
                    <strong>Bs {{ \Illuminate\Support\Number::format($openingAmount, 2) }}</strong>
                </div>
                <div class="row">
                    <span>Ventas cobradas en efectivo</span>
                    <strong>Bs {{ \Illuminate\Support\Number::format($cashSales, 2) }}</strong>
                </div>
                <div class="row">
                    <span>Ventas cobradas por QR</span>
                    <strong>Bs {{ \Illuminate\Support\Number::format($qrSales, 2) }}</strong>
                </div>
                <div class="row strong">
                    <span>Total cobrado del turno</span>
                    <strong>Bs {{ \Illuminate\Support\Number::format($salesTotal, 2) }}</strong>
                </div>
                <div class="muted">Este total incluye efectivo y QR.</div>
            </div>

            <div class="divider"></div>

            <div class="summary-box">
                <p class="summary-lead strong">2. Dinero que salio durante el turno</p>
                <div class="row">
                    <span>Egresos registrados</span>
                    <strong>Bs {{ \Illuminate\Support\Number::format($expensesTotal, 2) }}</strong>
                </div>
                <div class="muted">Aqui se reflejan compras, pagos o retiros registrados en la caja.</div>
            </div>

            <div class="divider"></div>

            <div class="summary-box summary-box--result">
                <p class="summary-lead strong">3. Lo que deberia haber al cerrar</p>
                <div class="row">
                    <span>Efectivo esperado en caja</span>
                    <strong>Bs {{ \Illuminate\Support\Number::format($expectedClosing, 2) }}</strong>
                </div>
                <div class="muted">Calculo: efectivo inicial + ventas en efectivo - egresos.</div>
            </div>

            <div class="divider"></div>

            <div class="summary-box {{ $resultBoxClass }}">
                <p class="summary-lead strong">4. Lo que realmente se conto</p>
                <div class="row">
                    <span>Dinero contado fisicamente</span>
                    <strong>Bs {{ \Illuminate\Support\Number::format($actualAmount, 2) }}</strong>
                </div>
                <div class="row strong {{ $differenceClass }}">
                    <span>{{ $differenceLabel }}</span>
                    <strong>Bs {{ \Illuminate\Support\Number::format($differenceAmount, 2) }}</strong>
                </div>
                <div class="muted">
                    @if ($differenceAmount < 0)
                        Falta dinero respecto a lo que el sistema esperaba.
                    @elseif ($differenceAmount > 0)
                        Sobro dinero respecto a lo que el sistema esperaba.
                    @else
                        El conteo coincide exactamente con lo esperado.
                    @endif
                </div>
            </div>
        </section>

        <div class="divider"></div>

        <section>
            <p class="section-title">Detalle de pedidos</p>
            <div class="row strong">
                <span>Total de pedidos del turno</span>
                <span>{{ $cashRegister->orders_count }}</span>
            </div>
            <div class="row">
                <span>Cancelados</span>
                <span>{{ $cashRegister->cancelled_orders_count }}</span>
            </div>

            <div class="divider"></div>

            @forelse ($cashRegister->orders as $order)
                <div class="order">
                    <div class="row">
                        <strong>{{ $order->order_number }}</strong>
                        <strong>Bs {{ \Illuminate\Support\Number::format((float) $order->total, 2) }}</strong>
                    </div>
                    <div class="muted">
                        {{ $order->created_at?->format('H:i') }}
                        - {{ $order->customer?->name ?? 'Sin cliente' }}
                        - {{ $order->payment_method ? (['cash' => 'Efectivo', 'qr' => 'QR'][$order->payment_method] ?? $order->payment_method) : 'Sin cobrar' }}
                        - {{ $order->status === 'cancelled' ? 'Cancelado' : 'Valido' }}
                    </div>
                </div>
            @empty
                <p class="muted center">No hay pedidos en esta caja.</p>
            @endforelse
        </section>

        <div class="divider"></div>

        <section>
            <p class="section-title">Detalle de egresos</p>
            <div class="row strong">
                <span>Egresos del turno</span>
                <span>{{ $cashRegister->expenses->count() }}</span>
            </div>

            <div class="divider"></div>

            @forelse ($cashRegister->expenses as $expense)
                <div class="order">
                    <div class="row">
                        <strong>{{ $expense->concept }}</strong>
                        <strong>Bs {{ \Illuminate\Support\Number::format((float) $expense->amount, 2) }}</strong>
                    </div>
                    <div class="muted">
                        {{ $expense->spent_at?->format('H:i') }}
                        - {{ $expense->user?->getFilamentName() ?: $expense->user?->username ?: 'Sin usuario' }}
                        @if ($expense->notes)
                            - {{ $expense->notes }}
                        @endif
                    </div>
                </div>
            @empty
                <p class="muted center">No hay egresos registrados en este turno.</p>
            @endforelse
        </section>

        @if ($cashRegister->opening_notes || $cashRegister->closing_notes)
            <div class="divider"></div>
            <section>
                <p class="section-title">Observaciones</p>
                @if ($cashRegister->opening_notes)
                    <div><strong>Nota inicial:</strong> {{ $cashRegister->opening_notes }}</div>
                @endif
                @if ($cashRegister->closing_notes)
                    <div><strong>Nota de cierre:</strong> {{ $cashRegister->closing_notes }}</div>
                @endif
            </section>
        @endif

        <div class="divider"></div>
        <p class="center muted">Arqueo generado {{ now()->format('d/m/Y H:i') }}</p>

        <div class="actions">
            <button class="button" onclick="window.print()">Imprimir arqueo</button>
            <button class="button" onclick="window.close()">Cerrar</button>
        </div>
    </main>

    <script>
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
</body>
</html>
