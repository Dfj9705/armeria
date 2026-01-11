<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccessoryResource\Pages;
use App\Filament\Resources\AccessoryResource\RelationManagers;
use App\Filament\Resources\AccessoryResource\RelationManagers\MovementsRelationManager;
use App\Models\Accessory;
use App\Models\Brand;
use App\Models\BrandModel;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccessoryResource extends Resource
{
    protected static ?string $model = Accessory::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $label = 'Accesorio';
    protected static ?string $pluralLabel = 'Accesorios';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Información')
                ->columns(2)
                ->schema([
                    Select::make('category_id')
                        ->label('Categoría')
                        ->relationship(
                            name: 'category',
                            titleAttribute: 'name',
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(80)
                                ->unique(table: 'accessory_categories', column: 'name', ignoreRecord: true),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Activo')
                                ->default(true),
                        ])
                        ->createOptionAction(
                            fn($action) => $action->modalHeading('Crear categoría')
                        ),

                    Select::make('brand_id')
                        ->label('Marca')
                        ->relationship(
                            name: 'brand',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn($query) => $query->where('type', 'accessory'),
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(80),
                            Radio::make('type')
                                ->options([
                                    'accessory' => 'Accesorio'
                                ])->default('accessory')
                                ->required(),
                            Toggle::make('is_active')
                                ->label('Activo')
                                ->default(true),
                        ])
                        ->createOptionAction(fn($action) => $action->modalHeading('Crear marca')),

                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(120),

                    TextInput::make('sku')
                        ->label('SKU / Código')
                        ->maxLength(60)
                        ->unique(ignoreRecord: true),

                    TextInput::make('unit_cost')
                        ->label('Costo unitario (ref.)')
                        ->numeric(),

                    TextInput::make('unit_price')
                        ->label('Precio unitario (ref.)')
                        ->numeric(),

                    TextInput::make('stock_min')
                        ->label('Stock mínimo')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),

                    Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),


                ]),

            Section::make('Descripción')
                ->schema([
                    Textarea::make('description')
                        ->label('Descripción')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),

            Section::make('Imágenes')->schema([
                FileUpload::make('images')
                    ->label('Fotos')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->openable()
                    ->imagePreviewHeight(120)
                    ->directory('accessories')
                    ->disk('public')
                    ->appendFiles()
                    ->maxFiles(8),
            ]),

            Section::make('Compatibilidad')
                ->columns(2)
                ->schema([
                    // Campo VIRTUAL (no existe en DB): solo sirve para filtrar
                    Select::make('compat_brand_id')
                        ->label('Marca compatible')
                        ->options(
                            fn() => Brand::query()
                                ->where('type', 'gun')
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            // al cambiar marca, limpiamos el modelo compatible
                            $set('compatible_brand_model_id', null);
                        })
                        ->afterStateHydrated(function (Set $set, Get $get, $state) {
                            if ($state)
                                return; // si ya hay algo, no tocar
                
                            $modelId = $get('compatible_brand_model_id');
                            if (!$modelId)
                                return;

                            $brandId = BrandModel::query()->whereKey($modelId)->value('brand_id');
                            $set('compat_brand_id', $brandId);
                        })
                        ->dehydrated(false) // <- clave: NO se guarda en accessories
                        ->placeholder('Seleccione una marca'),

                    // Campo REAL (sí existe en DB): compatible_brand_model_id
                    Select::make('compatible_brand_model_id')
                        ->label('Modelo compatible')
                        ->options(function (Get $get) {
                            $brandId = $get('compat_brand_id');
                            if (!$brandId)
                                return [];

                            return BrandModel::query()
                                ->where('brand_id', $brandId)
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->preload()
                        ->required()
                        ->disabled(fn(Get $get) => blank($get('compat_brand_id')))
                        ->placeholder('Seleccione un modelo')
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Nombre del modelo')
                                ->required()
                                ->maxLength(80),
                            Toggle::make('is_active')
                                ->label('Activo')
                                ->default(true),
                        ])
                        ->createOptionUsing(function (array $data, Get $get) {
                            return BrandModel::create([
                                'brand_id' => $get('compat_brand_id'),
                                'name' => $data['name'],
                                'is_active' => $data['is_active'] ?? true,
                            ])->getKey();
                        }),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('images.0')
                ->label('Foto')
                ->disk('public')
                ->circular(),
            Tables\Columns\TextColumn::make('category.name')->label('Categoría')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('brand.name')->label('Marca')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->label('Accesorio')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('sku')->label('SKU')->toggleable()->searchable(),
            Tables\Columns\TextColumn::make('compatibleBrandModel.brand.name')
                ->label('Marca compatible')
                ->toggleable()
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('compatibleBrandModel.name')
                ->label('Modelo compatible')
                ->toggleable()
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('current_stock')
                ->label('Stock')
                ->state(fn(Accessory $record) => $record->current_stock)
                ->sortable(),

            Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
        ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            MovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccessories::route('/'),
            'create' => Pages\CreateAccessory::route('/create'),
            'edit' => Pages\EditAccessory::route('/{record}/edit'),
        ];
    }
}
