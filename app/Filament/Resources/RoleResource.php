<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Permission;

class RoleResource extends BaseResource
{
    protected static ?string $model = Role::class;
    protected static string $permissionPrefix = 'roles';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Roles';
    protected static ?string $modelLabel = 'Rol';
    protected static ?string $pluralModelLabel = 'Roles';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del rol')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\Hidden::make('guard_name')
                    ->default('web'),

                Forms\Components\CheckboxList::make('permissions')
                    ->label('Permisos')
                    ->relationship('permissions', 'name')
                    ->options(
                        fn() => Permission::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->mapWithKeys(fn($name, $id) => [
                                $id => self::permissionLabel($name),
                            ])
                            ->toArray()
                    )
                    ->columns(3)
                    ->searchable()
                    ->bulkToggleable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Rol')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permisos')
                    ->counts('permissions')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    protected static function permissionLabel(string $permission): string
    {
        return match ($permission) {
            'view_branches' => 'Ver sucursales',
            'create_branches' => 'Crear sucursales',
            'update_branches' => 'Editar sucursales',
            'delete_branches' => 'Eliminar sucursales',

            'view_users' => 'Ver usuarios',
            'create_users' => 'Crear usuarios',
            'update_users' => 'Editar usuarios',
            'delete_users' => 'Eliminar usuarios',

            'view_weapons' => 'Ver armas',
            'create_weapons' => 'Crear armas',
            'update_weapons' => 'Editar armas',
            'delete_weapons' => 'Eliminar armas',

            'view_ammo' => 'Ver munición',
            'create_ammo' => 'Crear munición',
            'update_ammo' => 'Editar munición',
            'delete_ammo' => 'Eliminar munición',

            'view_accessories' => 'Ver accesorios',
            'create_accessories' => 'Crear accesorios',
            'update_accessories' => 'Editar accesorios',
            'delete_accessories' => 'Eliminar accesorios',

            'view_inventory_movements' => 'Ver movimientos de inventario',
            'create_inventory_movements' => 'Crear movimientos de inventario',

            'view_sales' => 'Ver ventas',
            'create_sales' => 'Crear ventas',
            'update_sales' => 'Editar ventas',
            'delete_sales' => 'Eliminar ventas',
            'confirm_sales' => 'Confirmar ventas',

            'view_reports' => 'Ver reportes',
            'export_reports' => 'Exportar reportes',

            'view_dashboard' => 'Ver dashboard',

            default => str($permission)
                ->replace('_', ' ')
                ->headline()
                ->toString(),
        };
    }
}
