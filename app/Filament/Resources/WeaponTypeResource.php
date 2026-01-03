<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeaponTypeResource\Pages;
use App\Filament\Resources\WeaponTypeResource\RelationManagers;
use App\Models\WeaponType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WeaponTypeResource extends Resource
{
    protected static ?string $model = WeaponType::class;

    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationLabel = 'Tipo de arma';
    protected static ?string $modelLabel = 'Tipo de arma';
    protected static ?string $pluralModelLabel = 'Tipo de armas';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),

            Forms\Components\TextInput::make('code')
                ->maxLength(30),

            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListWeaponTypes::route('/'),
            'create' => Pages\CreateWeaponType::route('/create'),
            'edit' => Pages\EditWeaponType::route('/{record}/edit'),
        ];
    }
}
