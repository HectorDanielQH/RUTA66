<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket {{ $order->order_number }}</title>
    <style>
        @page {
            margin: 6mm;
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

        .ticket {
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
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .attention-number {
            margin: 8px auto 4px;
            border: 2px solid #111827;
            border-radius: 10px;
            padding: 8px;
            text-align: center;
        }

        .attention-label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .attention-value {
            display: block;
            font-size: 28px;
            font-weight: 900;
            line-height: 1;
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
        }

        .item {
            margin: 0 0 8px;
        }

        .item-name {
            font-weight: 700;
        }

        .total {
            font-size: 15px;
            font-weight: 800;
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

            .ticket {
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
    <main class="ticket">
        <header class="center">
            <h1 class="brand">Ruta 66</h1>
            <div class="muted">Ticket de pedido</div>
            <div>{{ $order->created_at?->format('d/m/Y H:i') }}</div>
            <div class="attention-number">
                <span class="attention-label">Numero de atencion</span>
                <span class="attention-value">#{{ str_pad((string) $order->id, 4, '0', STR_PAD_LEFT) }}</span>
                <span>{{ $order->created_at?->format('H:i') }}</span>
            </div>
        </header>

        <div class="divider"></div>

        <section>
            <div class="row">
                <strong>Pedido</strong>
                <span>{{ $order->order_number }}</span>
            </div>
            <div class="row">
                <strong>Cliente</strong>
                <span>{{ $order->customer?->name ?? 'Sin cliente' }}</span>
            </div>
            @if ($order->phone)
                <div class="row">
                    <strong>Telefono</strong>
                    <span>{{ $order->phone }}</span>
                </div>
            @endif
            <div class="row">
                <strong>Tipo</strong>
                <span>{{ ['local' => 'Local', 'pickup' => 'Para recoger', 'delivery' => 'Delivery'][$order->order_type] ?? $order->order_type }}</span>
            </div>
            <div class="row">
                <strong>Pago</strong>
                <span>{{ ['cash' => 'Efectivo', 'qr' => 'QR', 'card' => 'Tarjeta', 'transfer' => 'Transferencia'][$order->payment_method] ?? $order->payment_method }}</span>
            </div>
            @if ($order->order_type === 'delivery')
                <div><strong>Zona:</strong> {{ $order->deliveryZone?->name ?? 'Sin zona' }}</div>
                <div><strong>Direccion:</strong> {{ $order->delivery_address ?: 'Sin direccion' }}</div>
                @if ($order->delivery_reference)
                    <div><strong>Referencia:</strong> {{ $order->delivery_reference }}</div>
                @endif
            @endif
        </section>

        <div class="divider"></div>

        <section>
            @foreach ($order->items as $item)
                <div class="item">
                    <div class="item-name">{{ $item->product_name }}</div>
                    <div class="row">
                        <span>{{ $item->quantity }} x Bs {{ \Illuminate\Support\Number::format((float) $item->unit_price, 2) }}</span>
                        <strong>Bs {{ \Illuminate\Support\Number::format((float) $item->subtotal, 2) }}</strong>
                    </div>
                    @if ($item->notes)
                        <div class="muted">Nota: {{ $item->notes }}</div>
                    @endif
                </div>
            @endforeach
        </section>

        <div class="divider"></div>

        <section>
            <div class="row">
                <span>Subtotal</span>
                <strong>Bs {{ \Illuminate\Support\Number::format((float) $order->subtotal, 2) }}</strong>
            </div>
            <div class="row">
                <span>Delivery</span>
                <strong>Bs {{ \Illuminate\Support\Number::format((float) $order->delivery_fee, 2) }}</strong>
            </div>
            <div class="row total">
                <span>Total</span>
                <strong>Bs {{ \Illuminate\Support\Number::format((float) $order->total, 2) }}</strong>
            </div>
        </section>

        @if ($order->notes)
            <div class="divider"></div>
            <section>
                <strong>Observaciones</strong>
                <div>{{ $order->notes }}</div>
            </section>
        @endif

        <div class="divider"></div>
        <p class="center muted">Gracias por su compra</p>

        <div class="actions">
            <button class="button" onclick="window.print()">Imprimir ticket</button>
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
