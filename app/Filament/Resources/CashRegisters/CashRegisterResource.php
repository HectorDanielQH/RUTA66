<?php

namespace App\Filament\Resources\CashRegisters;

use App\Filament\Resources\CashRegisters\Pages\CreateCashRegister;
use App\Filament\Resources\CashRegisters\Pages\EditCashRegister;
use App\Filament\Resources\CashRegisters\Pages\ListCashRegisters;
use App\Models\CashRegister;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class CashRegisterResource extends Resource
{
    protected static ?string $model = CashRegister::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Caja';

    protected static ?string $modelLabel = 'Caja';

    protected static ?string $pluralModelLabel = 'Cajas';

    protected static string | \UnitEnum | null $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || $user->hasRole('super_admin')) {
            return $query;
        }

        return $query->where('user_id', $user->getKey());
    }

    public static function getNavigationBadge(): ?string
    {
        $query = CashRegister::query()->where('status', 'open');
        $user = Auth::user();

        if ($user && ! $user->hasRole('super_admin')) {
            $query->where('user_id', $user->getKey());
        }

        $openRegisters = $query->count();

        return $openRegisters > 0 ? (string) $openRegisters : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    protected static function statusOptions(): array
    {
        return [
            'open' => 'Abierta',
            'closed' => 'Cerrada',
        ];
    }

    protected static function money(float | int | string | null $value): string
    {
        return 'Bs ' . Number::format((float) ($value ?? 0), 2);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Paso 1: Abrir caja')
                    ->description('Antes de vender, escribe cuanto efectivo hay al empezar el turno. Desde ese momento los pedidos se guardan en esta caja.')
                    ->schema([
                        TextInput::make('code')
                            ->label('Numero de caja')
                            ->disabled()
                            ->dehydrated()
                            ->placeholder('Se genera automaticamente'),
                        Select::make('user_id')
                            ->label('Atendido por')
                            ->options(fn (): array => User::query()->orderBy('nombres')->get()->mapWithKeys(
                                fn (User $user): array => [$user->id => $user->getFilamentName() ?: $user->username]
                            )->all())
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated(),
                        Select::make('status')
                            ->label('Estado')
                            ->options(static::statusOptions())
                            ->default('open')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('opening_amount')
                            ->label('Dinero con el que empieza')
                            ->numeric()
                            ->prefix('Bs')
                            ->default(0)
                            ->minValue(0)
                            ->required(),
                        DateTimePicker::make('opened_at')
                            ->label('Hora de apertura')
                            ->default(now())
                            ->seconds(false)
                            ->required(),
                        Textarea::make('opening_notes')
                            ->label('Nota inicial')
                            ->placeholder('Opcional: por ejemplo, billetes recibidos o algun detalle del turno.')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Paso 2: Cerrar caja')
                    ->description('Al terminar el turno, cuenta el efectivo real. El sistema compara lo esperado con lo contado.')
                    ->schema([
                        Placeholder::make('orders_count')
                            ->label('Pedidos en esta caja')
                            ->content(fn (?CashRegister $record): string => (string) ($record?->orders_count ?? 0)),
                        Placeholder::make('registered_sales')
                            ->label('Total cobrado')
                            ->content(fn (?CashRegister $record): string => static::money($record?->sales_total ?? 0)),
                        Placeholder::make('cash_sales')
                            ->label('Ventas en efectivo')
                            ->content(fn (?CashRegister $record): string => static::money($record?->cash_sales_total ?? 0)),
                        Placeholder::make('digital_sales')
                            ->label('QR / tarjeta / transferencia')
                            ->content(fn (?CashRegister $record): string => static::money(
                                ($record?->qr_sales_total ?? 0) + ($record?->card_sales_total ?? 0) + ($record?->transfer_sales_total ?? 0)
                            )),
                        Placeholder::make('expected_closing')
                            ->label('Efectivo esperado en caja')
                            ->content(fn (?CashRegister $record): string => static::money($record?->expected_closing_amount ?? 0)),
                        TextInput::make('actual_amount')
                            ->label('Dinero contado al final')
                            ->numeric()
                            ->prefix('Bs')
                            ->disabled(fn (?CashRegister $record): bool => $record?->status !== 'closed')
                            ->dehydrated(),
                        TextInput::make('difference_amount')
                            ->label('Diferencia')
                            ->numeric()
                            ->prefix('Bs')
                            ->disabled()
                            ->dehydrated(),
                        DateTimePicker::make('closed_at')
                            ->label('Hora de cierre')
                            ->seconds(false)
                            ->disabled()
                            ->dehydrated(),
                        Textarea::make('closing_notes')
                            ->label('Nota de cierre')
                            ->placeholder('Opcional: explica si falto o sobro dinero.')
                            ->rows(3)
                            ->disabled(fn (?CashRegister $record): bool => $record?->status !== 'closed')
                            ->dehydrated()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Turno de caja')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (CashRegister $record): string => $record->opened_at?->format('d/m/Y H:i') ?? ''),
                TextColumn::make('user.nombres')
                    ->label('Atendido por')
                    ->formatStateUsing(fn (CashRegister $record): string => $record->user?->getFilamentName() ?: $record->user?->username ?: 'Sin usuario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'open' ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state): string => static::statusOptions()[$state] ?? $state),
                TextColumn::make('orders_count')
                    ->label('Pedidos')
                    ->state(fn (CashRegister $record): int => $record->orders_count)
                    ->badge()
                    ->color('info'),
                TextColumn::make('opening_amount')
                    ->label('Inicio')
                    ->formatStateUsing(fn ($state): string => static::money($state)),
                TextColumn::make('sales_total')
                    ->label('Total cobrado')
                    ->state(fn (CashRegister $record): float => $record->sales_total)
                    ->description('Efectivo + QR + tarjeta + transferencia')
                    ->formatStateUsing(fn ($state): string => static::money($state)),
                TextColumn::make('cash_sales_total')
                    ->label('Ventas efectivo')
                    ->state(fn (CashRegister $record): float => $record->cash_sales_total)
                    ->formatStateUsing(fn ($state): string => static::money($state)),
                TextColumn::make('qr_sales_total')
                    ->label('QR')
                    ->state(fn (CashRegister $record): float => $record->qr_sales_total)
                    ->formatStateUsing(fn ($state): string => static::money($state)),
                TextColumn::make('card_sales_total')
                    ->label('Tarjeta')
                    ->state(fn (CashRegister $record): float => $record->card_sales_total)
                    ->formatStateUsing(fn ($state): string => static::money($state)),
                TextColumn::make('transfer_sales_total')
                    ->label('Transfer.')
                    ->state(fn (CashRegister $record): float => $record->transfer_sales_total)
                    ->formatStateUsing(fn ($state): string => static::money($state)),
                TextColumn::make('expected_closing_amount')
                    ->label('Efectivo esperado')
                    ->state(fn (CashRegister $record): float => $record->expected_closing_amount)
                    ->description('Inicio + ventas en efectivo')
                    ->formatStateUsing(fn ($state): string => static::money($state)),
                TextColumn::make('actual_amount')
                    ->label('Contado')
                    ->placeholder('Pendiente')
                    ->formatStateUsing(fn ($state): string => static::money($state)),
                TextColumn::make('difference_amount')
                    ->label('Diferencia')
                    ->formatStateUsing(fn ($state): string => static::money($state))
                    ->color(fn ($state): string => (float) $state < 0 ? 'danger' : ((float) $state > 0 ? 'warning' : 'success')),
                TextColumn::make('closed_at')
                    ->label('Cerrada')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Abierta')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(static::statusOptions()),
            ])
            ->defaultSort('opened_at', 'desc')
            ->recordActions([
                Action::make('closeRegister')
                    ->label('Cerrar turno')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->modalHeading('Cerrar turno de caja')
                    ->modalDescription('Cuenta el dinero fisico de la caja y escribelo aqui. El sistema calculara si falta o sobra.')
                    ->visible(fn (CashRegister $record): bool => $record->status === 'open')
                    ->form([
                        Placeholder::make('orders_count_preview')
                            ->label('Pedidos registrados')
                            ->content(fn (CashRegister $record): string => (string) $record->orders_count),
                        Placeholder::make('sales_preview')
                            ->label('Total cobrado')
                            ->content(fn (CashRegister $record): string => static::money($record->sales_total)),
                        Placeholder::make('cash_sales_preview')
                            ->label('Cobrado en efectivo')
                            ->content(fn (CashRegister $record): string => static::money($record->cash_sales_total)),
                        Placeholder::make('digital_sales_preview')
                            ->label('Cobros digitales')
                            ->content(fn (CashRegister $record): string => static::money(
                                $record->qr_sales_total + $record->card_sales_total + $record->transfer_sales_total
                            )),
                        Placeholder::make('expected_amount_preview')
                            ->label('Efectivo esperado en caja')
                            ->content(fn (CashRegister $record): string => static::money($record->expected_closing_amount)),
                        TextInput::make('actual_amount')
                            ->label('Dinero contado')
                            ->helperText('Escribe el dinero real que tienes en caja al cerrar.')
                            ->numeric()
                            ->prefix('Bs')
                            ->required(),
                        Textarea::make('closing_notes')
                            ->label('Nota de cierre')
                            ->placeholder('Opcional: por ejemplo, falto cambio, sobro efectivo o hubo una anulacion.')
                            ->rows(3),
                    ])
                    ->action(function (CashRegister $record, array $data): void {
                        $expectedAmount = $record->expected_closing_amount;
                        $actualAmount = (float) $data['actual_amount'];

                        $record->update([
                            'status' => 'closed',
                            'expected_amount' => $expectedAmount,
                            'actual_amount' => $actualAmount,
                            'difference_amount' => $actualAmount - $expectedAmount,
                            'closed_at' => now(),
                            'closing_notes' => $data['closing_notes'] ?? null,
                        ]);
                    }),
                Action::make('printReport')
                    ->label('Imprimir arqueo')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (CashRegister $record): string => route('cash-registers.report', $record))
                    ->openUrlInNewTab(),
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
            'index' => ListCashRegisters::route('/'),
            'create' => CreateCashRegister::route('/create'),
            'edit' => EditCashRegister::route('/{record}/edit'),
        ];
    }
}
