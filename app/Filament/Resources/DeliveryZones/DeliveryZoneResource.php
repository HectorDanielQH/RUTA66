<?php

namespace App\Filament\Resources\DeliveryZones;

use App\Filament\Resources\DeliveryZones\Pages\CreateDeliveryZone;
use App\Filament\Resources\DeliveryZones\Pages\EditDeliveryZone;
use App\Filament\Resources\DeliveryZones\Pages\ListDeliveryZones;
use App\Models\DeliveryZone;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class DeliveryZoneResource extends Resource
{
    protected static ?string $model = DeliveryZone::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Zonas de Delivery';

    protected static ?string $modelLabel = 'Zona de Delivery';

    protected static ?string $pluralModelLabel = 'Zonas de Delivery';

    protected static string | \UnitEnum | null $navigationGroup = 'Delivery';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('fee')
                    ->label('Costo de envio')
                    ->numeric()
                    ->prefix('Bs')
                    ->required()
                    ->minValue(0),
                TextInput::make('estimated_time_minutes')
                    ->label('Tiempo estimado (minutos)')
                    ->numeric()
                    ->integer()
                    ->required()
                    ->minValue(1),
                Textarea::make('description')
                    ->label('Descripcion')
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('fee')
                    ->label('Costo')
                    ->formatStateUsing(fn ($state): string => 'Bs ' . Number::format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('estimated_time_minutes')
                    ->label('Tiempo')
                    ->formatStateUsing(fn ($state): string => $state . ' min')
                    ->sortable(),
                IconColumn::make('is_active')->label('Activa')->boolean(),
                TextColumn::make('updated_at')->label('Actualizada')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
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
            'index' => ListDeliveryZones::route('/'),
            'create' => CreateDeliveryZone::route('/create'),
            'edit' => EditDeliveryZone::route('/{record}/edit'),
        ];
    }
}
