<?php

namespace App\Filament\Resources\CashExpenses;

use App\Filament\Resources\CashExpenses\Pages\CreateCashExpense;
use App\Filament\Resources\CashExpenses\Pages\EditCashExpense;
use App\Filament\Resources\CashExpenses\Pages\ListCashExpenses;
use App\Models\CashExpense;
use App\Models\CashRegister;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class CashExpenseResource extends Resource
{
    protected static ?string $model = CashExpense::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected static ?string $navigationLabel = 'Egresos';

    protected static ?string $modelLabel = 'Egreso';

    protected static ?string $pluralModelLabel = 'Egresos';

    protected static string | \UnitEnum | null $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin', 'cajero']) ?? false;
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin', 'cajero']) ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin', 'cajero']) ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        if (! $user || ! $record) {
            return false;
        }

        return $user->hasRole('super_admin')
            || ($user->hasRole('cajero') && $record->cashRegister?->user_id === $user->getKey());
    }

    public static function canDelete($record): bool
    {
        return static::canEdit($record);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['cashRegister.user', 'user']);
        $user = Auth::user();

        if (! $user || $user->hasRole('super_admin')) {
            return $query;
        }

        return $query->whereHas('cashRegister', fn (Builder $cashRegisterQuery): Builder => $cashRegisterQuery->where('user_id', $user->getKey()));
    }

    protected static function money(float | int | string | null $value): string
    {
        return 'Bs ' . Number::format((float) ($value ?? 0), 2);
    }

    protected static function selectedCashRegister(?int $cashRegisterId): ?CashRegister
    {
        if (! $cashRegisterId) {
            return null;
        }

        return CashRegister::query()
            ->with('user')
            ->find($cashRegisterId);
    }

    protected static function availableCashAmount(?CashRegister $cashRegister, ?CashExpense $record = null): float
    {
        if (! $cashRegister) {
            return 0;
        }

        $available = (float) $cashRegister->cash_sales_total - (float) $cashRegister->expenses_total;

        if ($record && $record->cash_register_id === $cashRegister->getKey()) {
            $available += (float) $record->amount;
        }

        return max(0, round($available, 2));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Paso 1: Elegir el turno')
                    ->description('Selecciona la caja o cierre del que saldra el dinero. El sistema te mostrara cuanto efectivo tiene disponible ese turno.')
                    ->schema([
                        Select::make('cash_register_id')
                            ->label('Turno de caja')
                            ->options(function (): array {
                                $query = CashRegister::query()->orderByDesc('opened_at');
                                $user = Auth::user();

                                if ($user && ! $user->hasRole('super_admin')) {
                                    $query->where('user_id', $user->getKey());
                                }

                                return $query
                                ->get()
                                ->mapWithKeys(fn (CashRegister $cashRegister): array => [
                                    $cashRegister->id => "{$cashRegister->code} - "
                                        . ($cashRegister->user?->getFilamentName() ?: $cashRegister->user?->username ?: 'Sin usuario')
                                        . ' - '
                                        . ($cashRegister->status === 'open' ? 'Abierta' : 'Cerrada')
                                        . ' - '
                                        . ($cashRegister->opened_at?->format('d/m/Y H:i') ?? 'Sin fecha'),
                                ])
                                ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->helperText('Puedes asignar el egreso a un turno abierto o a un cierre ya realizado, segun corresponda.')
                            ->required(),
                        Placeholder::make('selected_register_summary')
                            ->label('Resumen del turno seleccionado')
                            ->content(function (Get $get, ?CashExpense $record): string {
                                $cashRegister = static::selectedCashRegister((int) ($get('cash_register_id') ?? 0));

                                if (! $cashRegister) {
                                    return 'Selecciona un turno para ver su resumen.';
                                }

                                $attendedBy = $cashRegister->user?->getFilamentName() ?: $cashRegister->user?->username ?: 'Sin usuario';

                                return "Caja {$cashRegister->code} | {$attendedBy} | Estado: "
                                    . ($cashRegister->status === 'open' ? 'Abierta' : 'Cerrada')
                                    . ' | Apertura: '
                                    . ($cashRegister->opened_at?->format('d/m/Y H:i') ?? 'Sin fecha');
                            })
                            ->columnSpanFull(),
                        Placeholder::make('selected_register_money')
                            ->label('Efectivo fisico disponible de ese turno')
                            ->content(function (Get $get, ?CashExpense $record): string {
                                $cashRegister = static::selectedCashRegister((int) ($get('cash_register_id') ?? 0));

                                if (! $cashRegister) {
                                    return 'Bs 0.00';
                                }

                                return static::money(static::availableCashAmount($cashRegister, $record));
                            })
                            ->columnSpan(1),
                        Placeholder::make('selected_register_breakdown')
                            ->label('Como se calcula el efectivo disponible')
                            ->content(function (Get $get): string {
                                $cashRegister = static::selectedCashRegister((int) ($get('cash_register_id') ?? 0));

                                if (! $cashRegister) {
                                    return 'Primero elige un turno.';
                                }

                                return 'Ventas efectivo: ' . static::money($cashRegister->cash_sales_total)
                                    . ' | Egresos ya registrados: ' . static::money($cashRegister->expenses_total);
                            })
                            ->columnSpan(1),
                    ])
                    ->columns(2),
                Section::make('Paso 2: Registrar el egreso')
                    ->description('Escribe el motivo y el monto. El sistema no permitira guardar si el egreso supera el efectivo disponible del turno.')
                    ->schema([
                        TextInput::make('concept')
                            ->label('Motivo del egreso')
                            ->placeholder('Ej. compra de insumos, movilidad, cambio, limpieza')
                            ->maxLength(120)
                            ->required(),
                        TextInput::make('amount')
                            ->label('Monto retirado')
                            ->numeric()
                            ->prefix('Bs')
                            ->minValue(0.01)
                            ->live(onBlur: true)
                            ->helperText(function (Get $get, ?CashExpense $record): string {
                                $cashRegister = static::selectedCashRegister((int) ($get('cash_register_id') ?? 0));
                                $available = static::availableCashAmount($cashRegister, $record);

                                return 'Solo se toma en cuenta efectivo fisico. Disponible para egresar: ' . static::money($available);
                            })
                            ->rule(function (Get $get, ?CashExpense $record): Closure {
                                return function (string $attribute, $value, Closure $fail) use ($get, $record): void {
                                    $cashRegister = static::selectedCashRegister((int) ($get('cash_register_id') ?? 0));
                                    $available = static::availableCashAmount($cashRegister, $record);

                                    if (! $cashRegister) {
                                        $fail('Debes seleccionar un turno de caja valido.');

                                        return;
                                    }

                                    if ((float) $value > $available) {
                                        $fail('El egreso no puede superar el efectivo disponible del turno: ' . static::money($available) . '.');
                                    }
                                };
                            })
                            ->required(),
                        DateTimePicker::make('spent_at')
                            ->label('Fecha y hora')
                            ->default(now())
                            ->seconds(false)
                            ->required(),
                        Textarea::make('notes')
                            ->label('Detalle')
                            ->placeholder('Opcional: explica a quien se pago o por que se retiro el dinero.')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cashRegister.code')
                    ->label('Caja')
                    ->badge()
                    ->searchable(),
                TextColumn::make('cashRegister.user.nombres')
                    ->label('Turno de')
                    ->formatStateUsing(fn (CashExpense $record): string => $record->cashRegister?->user?->getFilamentName() ?: $record->cashRegister?->user?->username ?: 'Sin usuario')
                    ->searchable(),
                TextColumn::make('concept')
                    ->label('Motivo')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->formatStateUsing(fn ($state): string => 'Bs ' . Number::format((float) $state, 2))
                    ->sortable()
                    ->color('danger'),
                TextColumn::make('spent_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('user.username')
                    ->label('Registrado por')
                    ->formatStateUsing(fn (CashExpense $record): string => $record->user?->getFilamentName() ?: $record->user?->username ?: 'Sin usuario'),
                TextColumn::make('notes')
                    ->label('Detalle')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('cash_register_id')
                    ->label('Caja')
                    ->relationship('cashRegister', 'code'),
            ])
            ->defaultSort('spent_at', 'desc')
            ->recordActions([
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
            'index' => ListCashExpenses::route('/'),
            'create' => CreateCashExpense::route('/create'),
            'edit' => EditCashExpense::route('/{record}/edit'),
        ];
    }
}
