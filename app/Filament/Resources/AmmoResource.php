<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmmoResource\Pages;
use App\Filament\Resources\AmmoResource\RelationManagers;
use App\Models\Ammo;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AmmoResource extends Resource
{
    protected static ?string $model = Ammo::class;

    protected static ?string $navigationIcon = 'govicon-ammo';

    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $modelLabel = 'Municion';
    protected static ?string $pluralModelLabel = 'Municiones';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('brand_id')
                ->label('Marca')
                ->relationship(
                    name: 'brand',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn($query) => $query->where('type', 'ammunition'),
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
                            'ammunition' => 'Municion'
                        ])->default('ammunition')
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),
                ])
                ->createOptionAction(fn($action) => $action->modalHeading('Crear marca')),

            Select::make('caliber_id')
                ->label('Calibre')
                ->relationship('caliber', 'name', fn($query) => $query->where('is_active', true))
                ->searchable()
                ->preload()
                ->required()
                ->createOptionForm([
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(50),
                    TextInput::make('code')
                        ->label('Código')
                        ->maxLength(30),
                    Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),
                ])
                ->createOptionAction(fn($action) => $action->modalHeading('Crear calibre')),

            TextInput::make('name')
                ->label('Nombre / Variante')
                ->maxLength(120)
                ->placeholder('Ej: FMJ 124gr'),

            TextInput::make('price_per_box')
                ->label('Precio por caja')
                ->numeric()
                ->minValue(0)
                ->prefix('Q')
                ->required(),

            TextInput::make('rounds_per_box')
                ->label('Cartuchos por caja')
                ->numeric()
                ->minValue(1)
                ->default(50)
                ->required(),



            FileUpload::make('images')
                ->label('Imágenes')
                ->multiple()
                ->image()
                ->disk('public')
                ->directory('ammos')
                ->reorderable()
                ->openable()
                ->downloadable()
                ->appendFiles()
                ->columnSpanFull(),

            Textarea::make('description')
                ->label('Descripción')
                ->rows(4)
                ->columnSpanFull(),

            Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('images')
                    ->label('Foto')
                    ->circular()
                    ->getStateUsing(fn($record) => $record->images[0] ?? null),
                TextColumn::make('brand.name')
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('caliber.name')
                    ->label('Calibre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Variante')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('Precio')
                    ->money('GTQ', true)
                    ->sortable(),

                TextColumn::make('stock_boxes')
                    ->label('Stock (cajas)')
                    ->sortable(),

                TextColumn::make('stock_rounds')
                    ->label('Stock (cartuchos)')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MovementsRelationManager::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAmmos::route('/'),
            'create' => Pages\CreateAmmo::route('/create'),
            'edit' => Pages\EditAmmo::route('/{record}/edit'),
        ];
    }
}
