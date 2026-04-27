<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static string | \UnitEnum | null $navigationGroup = 'Administracion';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'username';

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de acceso')
                    ->description('Estos datos sirven para iniciar sesion en el panel.')
                    ->schema([
                        TextInput::make('username')
                            ->label('Usuario')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label('Contrasena')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText('En edicion, dejalo vacio si no quieres cambiar la contrasena.'),
                        Select::make('roles')
                            ->label('Rol')
                            ->relationship('roles', 'name')
                            ->options(fn (): array => Role::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->preload()
                            ->searchable()
                            ->required()
                            ->multiple(),
                    ])
                    ->columns(2),
                Section::make('Datos personales')
                    ->schema([
                        TextInput::make('nombres')
                            ->label('Nombres')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('apellido_paterno')
                            ->label('Apellido paterno')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('apellido_materno')
                            ->label('Apellido materno')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('cedula_identidad')
                            ->label('Cedula de identidad')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('email')
                            ->label('Correo')
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombres')
                    ->label('Nombre')
                    ->formatStateUsing(fn (User $record): string => $record->getFilamentName() ?: $record->username)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Rol')
                    ->badge(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->placeholder('Sin correo')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('roles');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
