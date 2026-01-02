<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeaponResource\Pages\CreateWeapon;
use App\Filament\Resources\WeaponResource\Pages\EditWeapon;
use App\Filament\Resources\WeaponResource\Pages\ListWeapons;
use App\Models\BrandModel;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Weapon;

class WeaponResource extends Resource
{
    protected static ?string $model = Weapon::class;
    protected static ?string $slug = 'armas';
    protected static ?string $navigationIcon = 'fas-gun';
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $modelLabel = 'Arma';
    protected static ?string $pluralModelLabel = 'Armas';
    protected static ?int $navigationSort = 1;



    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Datos del arma')->schema([
                TextInput::make('serial_number')
                    ->label('No. Serie')
                    ->maxLength(100)->required()
                    ->unique(ignoreRecord: true),

                Select::make('brand_id')
                    ->label('Marca')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn($set) => $set('brand_model_id', null)),
                Select::make('brand_model_id')
                    ->label('Modelo')
                    ->options(function (Get $get) {
                        $brandId = $get('brand_id');
                        if (!$brandId)
                            return [];
                        return BrandModel::query()
                            ->where('brand_id', $brandId)
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn(Get $get) => blank($get('brand_id')))
                    ->dehydrated(true),
                TextInput::make('caliber')->label('Calibre')->required()->maxLength(50),

                TextInput::make('magazine_capacity')
                    ->label('Capacidad del cargador')
                    ->numeric()
                    ->minValue(0),

                TextInput::make('barrel_length_mm')
                    ->label('Longitud del cañón (mm)')
                    ->numeric()
                    ->minValue(0),

                TextInput::make('price')
                    ->label('Precio')
                    ->numeric()
                    ->prefix('Q')
                    ->minValue(0)
                    ->required(),

                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'ACTIVE' => 'Activa',
                        'INACTIVE' => 'Baja',
                        'MAINTENANCE' => 'Mantenimiento',
                    ])->default('ACTIVE')->required(),

                Textarea::make('description')->label('Descripción')->columnSpanFull(),
            ])->columns(2),

            Section::make('Imágenes')->schema([
                FileUpload::make('images')
                    ->label('Fotos')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->directory('weapons')
                    ->disk('public')
                    ->maxFiles(8),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('images')
                    ->label('Foto')
                    ->circular()
                    ->getStateUsing(fn($record) => $record->images[0] ?? null),

                TextColumn::make('brand.name')->label('Marca')->sortable()->searchable(),
                TextColumn::make('brandModel.name')->label('Modelo')->sortable()->searchable(),
                TextColumn::make('caliber')->label('Calibre')->sortable(),
                TextColumn::make('price')->label('Precio')->money('GTQ', true),
                TextColumn::make('stock')->label('Stock')->badge(),
                TextColumn::make('status')->label('Estado')->badge(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWeapons::route('/'),
            'create' => CreateWeapon::route('/create'),
            'edit' => EditWeapon::route('/{record}/edit'),
        ];
    }

}
