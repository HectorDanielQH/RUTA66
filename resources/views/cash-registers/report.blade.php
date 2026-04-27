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
    <main class="report">
        <header class="center">
            <h1 class="brand">Ruta 66</h1>
            <div class="title">Arqueo de caja</div>
            <div>{{ $cashRegister->code }}</div>
        </header>

        <div class="divider"></div>

        <section>
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
                <span>{{ $cashRegister->status === 'open' ? 'Abierta' : 'Cerrada' }}</span>
            </div>
        </section>

        <div class="divider"></div>

        <section>
            <div class="row">
                <span>Efectivo inicial</span>
                <strong>Bs {{ \Illuminate\Support\Number::format((float) $cashRegister->opening_amount, 2) }}</strong>
            </div>
            <div class="row">
                <span>Ventas efectivo</span>
                <strong>Bs {{ \Illuminate\Support\Number::format($cashRegister->cash_sales_total, 2) }}</strong>
            </div>
            <div class="row">
                <span>QR</span>
                <strong>Bs {{ \Illuminate\Support\Number::format($cashRegister->qr_sales_total, 2) }}</strong>
            </div>
            <div class="row">
                <span>Tarjeta</span>
                <strong>Bs {{ \Illuminate\Support\Number::format($cashRegister->card_sales_total, 2) }}</strong>
            </div>
            <div class="row">
                <span>Transferencia</span>
                <strong>Bs {{ \Illuminate\Support\Number::format($cashRegister->transfer_sales_total, 2) }}</strong>
            </div>
            <div class="divider"></div>
            <div class="row strong">
                <span>Total cobrado</span>
                <strong>Bs {{ \Illuminate\Support\Number::format($cashRegister->sales_total, 2) }}</strong>
            </div>
            <div class="muted">Incluye efectivo, QR, tarjeta y transferencia.</div>
            <div class="row strong">
                <span>Efectivo esperado</span>
                <strong>Bs {{ \Illuminate\Support\Number::format($cashRegister->expected_closing_amount, 2) }}</strong>
            </div>
            <div class="muted">Solo efectivo: inicial + ventas en efectivo.</div>
            <div class="row">
                <span>Efectivo contado</span>
                <strong>Bs {{ \Illuminate\Support\Number::format((float) $cashRegister->actual_amount, 2) }}</strong>
            </div>
            <div class="row strong {{ (float) $cashRegister->difference_amount < 0 ? 'danger' : 'success' }}">
                <span>Diferencia</span>
                <strong>Bs {{ \Illuminate\Support\Number::format((float) $cashRegister->difference_amount, 2) }}</strong>
            </div>
        </section>

        <div class="divider"></div>

        <section>
            <div class="row strong">
                <span>Pedidos</span>
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
                        - {{ ['cash' => 'Efectivo', 'qr' => 'QR', 'card' => 'Tarjeta', 'transfer' => 'Transferencia'][$order->payment_method] ?? $order->payment_method }}
                        - {{ $order->status === 'cancelled' ? 'Cancelado' : 'Valido' }}
                    </div>
                </div>
            @empty
                <p class="muted center">No hay pedidos en esta caja.</p>
            @endforelse
        </section>

        @if ($cashRegister->opening_notes || $cashRegister->closing_notes)
            <div class="divider"></div>
            <section>
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
