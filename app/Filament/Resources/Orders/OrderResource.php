<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Pedidos';

    protected static ?string $modelLabel = 'Pedido';

    protected static ?string $pluralModelLabel = 'Pedidos';

    protected static string | \UnitEnum | null $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || $user->hasRole('super_admin')) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where('user_id', $user->getKey())
                ->orWhereHas('cashRegister', fn (Builder $query): Builder => $query->where('user_id', $user->getKey()));
        });
    }

    protected static function orderStatusOptions(): array
    {
        return [
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmado',
            'preparing' => 'En preparacion',
            'ready' => 'Listo',
            'on_the_way' => 'En camino',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
        ];
    }

    protected static function orderTypeOptions(): array
    {
        return [
            'local' => 'Local',
            'pickup' => 'Para recoger',
            'delivery' => 'Delivery',
        ];
    }

    protected static function paymentMethodOptions(): array
    {
        return [
            'cash' => 'Efectivo',
            'qr' => 'QR',
            'card' => 'Tarjeta',
            'transfer' => 'Transferencia',
        ];
    }

    protected static function statusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'gray',
            'confirmed' => 'info',
            'preparing' => 'warning',
            'ready' => 'primary',
            'on_the_way' => 'danger',
            'delivered' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    protected static function updateItemSubtotal(Get $get, Set $set): void
    {
        $quantity = max(1, (int) ($get('quantity') ?? 1));
        $unitPrice = (float) ($get('unit_price') ?? 0);

        $set('subtotal', round($quantity * $unitPrice, 2));
    }

    protected static function updateOrderTotals(Get $get, Set $set, string $prefix = ''): void
    {
        $items = $get($prefix . 'items') ?? [];

        $subtotal = collect($items)->sum(function (array $item): float {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            return $quantity * $unitPrice;
        });

        $deliveryFee = (float) ($get($prefix . 'delivery_fee') ?? 0);

        $set($prefix . 'subtotal', round($subtotal, 2));
        $set($prefix . 'total', round($subtotal + $deliveryFee, 2));
    }

    protected static function calculateOrderTotalFromState(Get $get): string
    {
        $items = $get('items') ?? [];

        $subtotal = collect($items)->sum(function (array $item): float {
            if (isset($item['subtotal'])) {
                return (float) $item['subtotal'];
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            return $quantity * $unitPrice;
        });

        $deliveryFee = (float) ($get('delivery_fee') ?? 0);

        return 'Bs ' . Number::format($subtotal + $deliveryFee, 2);
    }

    protected static function productPreview(Get $get): HtmlString | string
    {
        $product = Product::find($get('product_id'));

        if (! $product) {
            return 'Selecciona un producto para ver su imagen.';
        }

        if (! $product->image) {
            return "Este producto aun no tiene imagen cargada. Stock disponible: {$product->stock}.";
        }

        $imageUrl = Storage::disk('public')->url($product->image);

        return new HtmlString(
            '<div style="display:flex;align-items:center;gap:12px;border:1px solid #e7e5e4;border-radius:14px;background:#fff;padding:12px;">'
            . '<img src="' . e($imageUrl) . '" alt="' . e($product->name) . '" style="width:88px;height:88px;border-radius:12px;object-fit:cover;border:1px solid #f5f5f4;">'
            . '<div>'
            . '<p style="margin:0;color:#1c1917;font-weight:800;">' . e($product->name) . '</p>'
            . '<p style="margin:4px 0 0;color:#57534e;font-size:13px;">Stock: ' . e((string) $product->stock) . ' | Precio: Bs ' . Number::format((float) $product->price, 2) . '</p>'
            . '</div>'
            . '</div>'
        );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del pedido')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->options(fn (): array => Customer::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                $customer = Customer::find($state);

                                if (! $customer) {
                                    $set('phone', null);
                                    $set('delivery_address', null);
                                    $set('delivery_reference', null);

                                    return;
                                }

                                $set('phone', $customer->phone);

                                if ($get('order_type') === 'delivery') {
                                    $set('delivery_address', $customer->address);
                                    $set('delivery_reference', $customer->reference);
                                }
                            }),
                        Select::make('order_type')
                            ->label('Tipo de pedido')
                            ->options(static::orderTypeOptions())
                            ->default('local')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                if ($state !== 'delivery') {
                                    return;
                                }

                                $customer = Customer::find($get('customer_id'));

                                if (! $customer) {
                                    return;
                                }

                                $set('delivery_address', $customer->address);
                                $set('delivery_reference', $customer->reference);
                            }),
                        Select::make('status')
                            ->label('Estado')
                            ->options(static::orderStatusOptions())
                            ->default('pending')
                            ->required(),
                        Select::make('payment_method')
                            ->label('Forma de pago')
                            ->options(static::paymentMethodOptions())
                            ->default('cash')
                            ->required()
                            ->helperText('Si no es efectivo, no se contara como dinero fisico en caja.'),
                        Select::make('delivery_zone_id')
                            ->label('Zona de delivery')
                            ->options(fn (): array => DeliveryZone::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn (Get $get): bool => $get('order_type') === 'delivery')
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                $zone = DeliveryZone::find($state);

                                if (! $zone) {
                                    $set('delivery_fee', 0);
                                    $set('estimated_delivery_time_minutes', null);
                                    static::updateOrderTotals($get, $set);

                                    return;
                                }

                                $set('delivery_fee', $zone->fee);
                                $set('estimated_delivery_time_minutes', $zone->estimated_time_minutes);
                                static::updateOrderTotals($get, $set);
                            }),
                        TextInput::make('phone')
                            ->label('Telefono de contacto')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('estimated_delivery_time_minutes')
                            ->label('Tiempo estimado (min)')
                            ->numeric()
                            ->integer()
                            ->disabled()
                            ->dehydrated()
                            ->visible(fn (Get $get): bool => $get('order_type') === 'delivery'),
                        Textarea::make('delivery_address')
                            ->label('Direccion de entrega')
                            ->rows(2)
                            ->visible(fn (Get $get): bool => $get('order_type') === 'delivery')
                            ->columnSpanFull(),
                        Textarea::make('delivery_reference')
                            ->label('Referencia de entrega')
                            ->rows(2)
                            ->visible(fn (Get $get): bool => $get('order_type') === 'delivery')
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Detalle del pedido')
                    ->schema([
                        Repeater::make('items')
                            ->label('Productos')
                            ->relationship()
                            ->reorderable(false)
                            ->defaultItems(1)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                static::updateOrderTotals($get, $set);
                            })
                            ->schema([
                                Select::make('product_id')
                                    ->label('Producto')
                                    ->options(fn (): array => Product::query()
                                        ->where('is_available', true)
                                        ->where('stock', '>', 0)
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (Product $product): array => [
                                            $product->id => "{$product->name} - Stock: {$product->stock}",
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                        $product = Product::find($state);

                                        if (! $product) {
                                            return;
                                        }

                                        $set('product_name', $product->name);
                                        $set('unit_price', $product->price);
                                        $set('quantity', 1);
                                        $set('subtotal', (float) $product->price);
                                        static::updateOrderTotals($get, $set, '../../');
                                    }),
                                TextInput::make('product_name')
                                    ->label('Nombre del producto')
                                    ->disabled()
                                    ->dehydrated(),
                                Placeholder::make('product_preview')
                                    ->label('Vista previa')
                                    ->content(fn (Get $get): HtmlString | string => static::productPreview($get))
                                    ->columnSpanFull(),
                                TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->integer()
                                    ->default(1)
                                    ->minValue(1)
                                    ->helperText(function (Get $get): string {
                                        $product = Product::find($get('product_id'));

                                        return $product ? "Disponible en inventario: {$product->stock}" : 'Selecciona un producto para ver el stock.';
                                    })
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        static::updateItemSubtotal($get, $set);
                                        static::updateOrderTotals($get, $set, '../../');
                                    }),
                                TextInput::make('unit_price')
                                    ->label('Precio unitario')
                                    ->numeric()
                                    ->prefix('Bs')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        static::updateItemSubtotal($get, $set);
                                        static::updateOrderTotals($get, $set, '../../');
                                    }),
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Bs')
                                    ->disabled()
                                    ->dehydrated(),
                                Textarea::make('notes')
                                    ->label('Notas del item')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Totales')
                    ->schema([
                        TextInput::make('delivery_fee')
                            ->label('Costo de envio')
                            ->numeric()
                            ->prefix('Bs')
                            ->live()
                            ->default(0)
                            ->minValue(0)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                static::updateOrderTotals($get, $set);
                            }),
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('Bs')
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->prefix('Bs')
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                        Placeholder::make('total_to_charge')
                            ->label('Total a cobrar')
                            ->content(fn (Get $get): string => static::calculateOrderTotalFromState($get)),
                        Placeholder::make('totals_help')
                            ->label('Caja')
                            ->content('El pedido se registrara en la caja abierta del usuario actual.'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Pedido')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Order $record): ?string => $record->phone),
                TextColumn::make('order_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => static::orderTypeOptions()[$state] ?? $state),
                TextColumn::make('deliveryZone.name')
                    ->label('Zona')
                    ->placeholder('Sin zona')
                    ->toggleable(),
                TextColumn::make('cashRegister.code')
                    ->label('Caja')
                    ->placeholder('Sin caja')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => static::statusColor($state))
                    ->formatStateUsing(fn (string $state): string => static::orderStatusOptions()[$state] ?? $state),
                TextColumn::make('payment_method')
                    ->label('Pago')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'qr' => 'info',
                        'card' => 'warning',
                        'transfer' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => static::paymentMethodOptions()[$state] ?? $state),
                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => 'Bs ' . Number::format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn (Order $record): string => $record->created_at?->format('d/m/Y H:i') ?? ''),
            ])
            ->filters([
                SelectFilter::make('order_type')
                    ->label('Tipo')
                    ->options(static::orderTypeOptions()),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(static::orderStatusOptions()),
                SelectFilter::make('payment_method')
                    ->label('Forma de pago')
                    ->options(static::paymentMethodOptions()),
                SelectFilter::make('delivery_zone_id')
                    ->label('Zona')
                    ->relationship('deliveryZone', 'name'),
                TernaryFilter::make('today')
                    ->label('Solo pedidos de hoy')
                    ->queries(
                        true: fn ($query) => $query->whereDate('created_at', today()),
                        false: fn ($query) => $query,
                        blank: fn ($query) => $query,
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('confirm')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn (Order $record): bool => $record->status === 'pending')
                    ->action(fn (Order $record): bool => $record->update(['status' => 'confirmed'])),
                Action::make('prepare')
                    ->label('Preparar')
                    ->icon('heroicon-o-fire')
                    ->color('warning')
                    ->visible(fn (Order $record): bool => in_array($record->status, ['pending', 'confirmed'], true))
                    ->action(fn (Order $record): bool => $record->update(['status' => 'preparing'])),
                Action::make('ready')
                    ->label('Listo')
                    ->icon('heroicon-o-hand-thumb-up')
                    ->color('primary')
                    ->visible(fn (Order $record): bool => $record->status === 'preparing')
                    ->action(fn (Order $record): bool => $record->update(['status' => 'ready'])),
                Action::make('dispatch')
                    ->label('En camino')
                    ->icon('heroicon-o-truck')
                    ->color('danger')
                    ->visible(fn (Order $record): bool => $record->order_type === 'delivery' && $record->status === 'ready')
                    ->action(fn (Order $record): bool => $record->update(['status' => 'on_the_way'])),
                Action::make('deliver')
                    ->label('Entregar')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Order $record): bool => in_array($record->status, ['ready', 'on_the_way'], true))
                    ->action(fn (Order $record): bool => $record->update(['status' => 'delivered'])),
                Action::make('printTicket')
                    ->label('Imprimir ticket')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Order $record): string => route('orders.ticket', $record))
                    ->openUrlInNewTab(),
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => ! in_array($record->status, ['delivered', 'cancelled'], true))
                    ->action(function (Order $record): bool {
                        $updated = $record->update(['status' => 'cancelled']);
                        $record->restoreItemsToStock();

                        return $updated;
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
