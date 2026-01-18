<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\WeaponUnit;
use App\Models\Ammo;
use App\Models\Accessory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $modelLabel = 'Venta';
    protected static ?string $pluralModelLabel = 'Ventas';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Encabezado')
                ->columnSpan('full')
                ->disabled(fn($record) => $record?->items->isNotEmpty() && $record?->status !== 'draft')
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('Cliente')
                        ->options(fn() => Customer::query()->orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload()

                        ->required(),

                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'draft' => 'Borrador',
                        ])
                        ->default('draft')
                        ->visible(fn($record) => $record?->status === 'draft')
                        ->required(),

                ]),

            Forms\Components\Section::make('Ítems')
                ->disabled(fn($record) => $record?->items->isNotEmpty() && $record?->status !== 'draft')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->afterStateHydrated(function ($state, Forms\Set $set) {
                            // $state es el array de items que vienen de la BD
                            if (!is_array($state))
                                return;

                            foreach ($state as $index => $row) {
                                $type = $row['sellable_type'] ?? null;
                                $id = $row['sellable_id'] ?? null;
                                $meta = $row['meta'] ?? null;

                                if ($type === WeaponUnit::class) {
                                    $set("items.{$index}.kind", 'weapon');
                                    $set("items.{$index}.weapon_unit_id", $id);
                                }

                                if ($type === Ammo::class) {
                                    $set("items.{$index}.kind", 'ammo');
                                    $set("items.{$index}.ammo_id", $id);

                                    $uom = $row['uom_snapshot'] ?? 'UNI';
                                    if ($uom === 'CJ') {
                                        $set("items.{$index}.ammo_mode", 'box');
                                        $set("items.{$index}.ammo_value", data_get($meta, 'boxes', $row['qty'] ?? 1));
                                    } else {
                                        $set("items.{$index}.ammo_mode", 'round');
                                        $set("items.{$index}.ammo_value", data_get($meta, 'rounds', $row['qty'] ?? 1));
                                    }
                                }

                                if ($type === Accessory::class) {
                                    $set("items.{$index}.kind", 'accessory');
                                    $set("items.{$index}.accessory_id", $id);
                                }
                            }
                        })
                        ->defaultItems(0)
                        ->columns(6)
                        ->schema([
                            Forms\Components\Select::make('kind')
                                ->label('Tipo')
                                ->options([
                                    'weapon' => 'Arma (serie)',
                                    'ammo' => 'Munición',
                                    'accessory' => 'Accesorio',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set) {
                                    // reset campos
                                    $set('sellable_type', null);
                                    $set('sellable_id', null);
                                    $set('qty', 1);
                                    $set('uom_snapshot', 'UNI');
                                    $set('meta', null);
                                    $set('description_snapshot', '');
                                })
                                ->columnSpan(2),

                            // ===== ARMA (WeaponUnit) =====
                            Forms\Components\Select::make('weapon_unit_id')
                                ->label('Serie')
                                ->options(
                                    fn() => WeaponUnit::query()
                                        ->where('status', 'in_stock')
                                        ->orWhere('status', 'reserved')
                                        ->orderBy('serial_number')
                                        ->pluck('serial_number', 'id')
                                        ->toArray()
                                )
                                ->searchable()
                                ->preload()
                                ->live()
                                ->visible(fn(Forms\Get $get) => $get('kind') === 'weapon')
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                    if (!$state)
                                        return;

                                    $wu = WeaponUnit::with('weapon')->find($state);
                                    if (!$wu)
                                        return;

                                    // set polymorphic
                                    $set('sellable_type', WeaponUnit::class);
                                    $set('sellable_id', $wu->id);

                                    $set('qty', 1);
                                    $set('uom_snapshot', 'UNI');

                                    $brand = $wu->weapon?->brand?->name ?? 'Arma';
                                    $type = $wu->weapon?->type?->name ?? 'Arma';
                                    $model = $wu->weapon?->brandModel?->name ?? 'Modelo';
                                    $caliber = $wu->weapon?->caliber?->name ?? 'Calibre';
                                    $desc = $type . ' - ' . $brand . ' - ' . $model . ' - ' . $caliber . ' - Serie: ' . $wu->serial_number;

                                    $set('description_snapshot', $desc);
                                    $set('meta', [
                                        'serial' => $wu->serial_number,
                                        'weapon_id' => $wu->weapon_id,
                                    ]);

                                    // precio sugerido: el del catálogo weapon si existe
                                    if ($wu->weapon && $wu->weapon->price) {
                                        $set('unit_price', (float) $wu->weapon->price);
                                    }
                                })
                                ->dehydrated(false)
                                ->columnSpan(2),

                            // ===== MUNICIÓN =====
                            Forms\Components\Select::make('ammo_id')
                                ->label('Munición')
                                ->options(fn() => Ammo::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->visible(fn(Forms\Get $get, Forms\Set $set) => $get('kind') === 'ammo')
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    if (!$state)
                                        return;
                                    $set('sellable_type', Ammo::class);
                                    $set('sellable_id', $state);
                                    // description se rellena en blur/guardado si quieres; aquí básico:
                        

                                    $set('description_snapshot', 'Munición');
                                    $id = $state;
                                    $set('ammo_id', $id);
                                    $ammo = Ammo::find($id);
                                    $set('unit_price', $ammo->price_per_box);
                                })
                                ->columnSpan(2),

                            Forms\Components\Select::make('ammo_mode')
                                ->label('Modo')
                                ->options([
                                    'box' => 'Caja (CJ)',
                                    'round' => 'Suelta (UNI)',
                                ])
                                ->live()
                                ->visible(fn(Forms\Get $get) => $get('kind') === 'ammo')
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                    $ammo_id = $get('ammo_id');
                                    $ammo = Ammo::find($ammo_id);
                                    if ($state === 'box') {
                                        $set('uom_snapshot', 'CJ');
                                        $set('qty', 1);
                                        $set('meta', ['boxes' => 1, 'rounds' => null]);
                                        $set('unit_price', $ammo->price_per_box);
                                    } else {
                                        $set('uom_snapshot', 'UNI');
                                        $set('qty', 1);
                                        $set('meta', ['boxes' => null, 'rounds' => 1]);
                                        $set('unit_price', $ammo->price_per_box / $ammo->rounds_per_box * 1);
                                    }
                                    $set('ammo_value', 1);
                                })
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('ammo_value')
                                ->label(fn(Forms\Get $get) => $get('ammo_mode') === 'round' ? 'Cartuchos' : 'Cajas')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->live()
                                ->visible(fn(Forms\Get $get) => $get('kind') === 'ammo')
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                    $state = (int) $state;
                                    $ammo_id = $get('ammo_id');
                                    $ammo = Ammo::find($ammo_id);
                                    if ($state < 1)
                                        $state = 1;

                                    if ($get('ammo_mode') === 'box') {
                                        $set('qty', $state);
                                        $set('uom_snapshot', 'CJ');
                                        $set('meta', ['boxes' => $state, 'rounds' => null]);
                                        $set('unit_price', $ammo->price_per_box * $state);
                                    } else {
                                        $set('qty', $state);
                                        $set('uom_snapshot', 'UNI');
                                        $set('meta', ['boxes' => null, 'rounds' => $state]);
                                        $set('unit_price', $ammo->price_per_box / $ammo->rounds_per_box * $state);
                                    }
                                })
                                ->columnSpan(2),

                            // ===== ACCESORIO =====
                            Forms\Components\Select::make('accessory_id')
                                ->label('Accesorio')
                                ->options(fn() => Accessory::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->visible(fn(Forms\Get $get) => $get('kind') === 'accessory')
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    if (!$state)
                                        return;
                                    $set('sellable_type', Accessory::class);
                                    $set('sellable_id', $state);
                                    $set('uom_snapshot', 'UNI');
                                    $set('description_snapshot', 'Accesorio');
                                    $set('unit_price', Accessory::find($state)->unit_price * 1);
                                    $set('qty', 1);
                                })
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('qty')
                                ->label('Cantidad')
                                ->numeric()
                                ->minValue(1)
                                ->live()
                                ->default(1)
                                ->visible(fn(Forms\Get $get) => $get('kind') == 'accessory')
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                    if ($get('kind') !== 'accessory')
                                        return;
                                    $accesory = Accessory::find($get('sellable_id'));
                                    $set('unit_price', $accesory->unit_price * $state);
                                })
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('discount')
                                ->label('Descuento')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->step(0.01)
                                ->disabled(fn($record) => $record?->status !== 'draft')
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('unit_price')
                                ->label('Precio unit.')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('authorization_number')
                                ->label('Nro. Autorización')
                                ->required()
                                ->visible(fn(Forms\Get $get) => $get('kind') !== 'accessory')
                                ->columnSpan(3)
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                    if (!$state)
                                        return;

                                    $set('description_snapshot', $get('description_snapshot') . ' - Autorización: ' . $state);
                                }),

                            Forms\Components\Hidden::make('sellable_type'),
                            Forms\Components\Hidden::make('sellable_id'),
                            Forms\Components\Hidden::make('description_snapshot'),
                            Forms\Components\Hidden::make('uom_snapshot'),
                            Forms\Components\Hidden::make('meta'),
                        ])
                        ->disableItemMovement()
                        ->addActionLabel('Agregar ítem'),
                ]),

            Forms\Components\Section::make('Totales')
                ->columns(3)
                ->disabled(fn($record) => $record?->status !== 'draft')
                ->schema([
                    Forms\Components\TextInput::make('subtotal')->disabled()->dehydrated(),
                    Forms\Components\TextInput::make('tax')->disabled()->dehydrated(),
                    Forms\Components\TextInput::make('total')->disabled()->dehydrated(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->sortable()->badge(),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('GTQ')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Creada'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()->disabled(fn($record) => $record?->status !== 'draft'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
