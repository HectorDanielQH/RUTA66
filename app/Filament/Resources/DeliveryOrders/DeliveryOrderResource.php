<?php

namespace App\Filament\Resources\DeliveryOrders;

use App\Filament\Resources\DeliveryOrders\Pages\CreateDeliveryOrder;
use App\Filament\Resources\DeliveryOrders\Pages\EditDeliveryOrder;
use App\Filament\Resources\DeliveryOrders\Pages\ListDeliveryOrders;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class DeliveryOrderResource extends OrderResource
{
    protected static ?string $slug = 'delivery-orders';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Pedidos Delivery';

    protected static ?string $modelLabel = 'Pedido Delivery';

    protected static ?string $pluralModelLabel = 'Pedidos Delivery';

    protected static string | \UnitEnum | null $navigationGroup = 'Delivery';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('order_type', 'delivery');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Pedido')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (Order $record): string => $record->created_at?->format('d/m/Y H:i') ?? ''),
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Order $record): ?string => $record->phone),
                TextColumn::make('delivery_address')
                    ->label('Direccion')
                    ->searchable()
                    ->wrap()
                    ->limit(55)
                    ->tooltip(fn (Order $record): ?string => $record->delivery_address),
                TextColumn::make('delivery_reference')
                    ->label('Referencia')
                    ->wrap()
                    ->limit(45)
                    ->placeholder('Sin referencia')
                    ->toggleable(),
                TextColumn::make('deliveryZone.name')
                    ->label('Zona')
                    ->placeholder('Sin zona')
                    ->badge()
                    ->sortable(),
                TextColumn::make('estimated_delivery_time_minutes')
                    ->label('Tiempo')
                    ->formatStateUsing(fn ($state): string => $state ? "{$state} min" : 'Sin tiempo')
                    ->badge()
                    ->color('info'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => static::statusColor($state))
                    ->formatStateUsing(fn (string $state): string => static::orderStatusOptions()[$state] ?? $state),
                TextColumn::make('payment_method')
                    ->label('Pago')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'cash' => 'success',
                        'qr' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => static::paymentMethodLabel($state)),
                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => 'Bs ' . Number::format((float) $state, 2))
                    ->sortable(),
            ])
            ->filters([
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
                    ->label('Solo deliverys de hoy')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereDate('created_at', today()),
                        false: fn (Builder $query): Builder => $query,
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('prepare')
                    ->label('En preparacion')
                    ->icon('heroicon-o-fire')
                    ->color('warning')
                    ->visible(fn (Order $record): bool => ! in_array($record->status, ['preparing', 'delivered', 'cancelled'], true))
                    ->action(fn (Order $record): bool => $record->update([
                        'status' => 'preparing',
                        'payment_method' => null,
                    ])),
                Action::make('deliver')
                    ->label('Cobrar y entregar')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === 'preparing')
                    ->form([
                        Select::make('payment_method')
                            ->label('Forma de pago')
                            ->options(static::paymentMethodOptions())
                            ->required(),
                    ])
                    ->action(fn (Order $record, array $data): bool => $record->update([
                        'status' => 'delivered',
                        'payment_method' => $data['payment_method'],
                    ])),
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
                        $updated = $record->update([
                            'status' => 'cancelled',
                            'payment_method' => null,
                        ]);
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

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveryOrders::route('/'),
            'create' => CreateDeliveryOrder::route('/create'),
            'edit' => EditDeliveryOrder::route('/{record}/edit'),
        ];
    }
}
