<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CaliberResource\Pages;
use App\Filament\Resources\CaliberResource\RelationManagers;
use App\Models\Caliber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CaliberResource extends Resource
{
    protected static ?string $model = Caliber::class;
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $modelLabel = 'Calibre';
    protected static ?string $pluralModelLabel = 'Calibres';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(50)
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('code')
                ->label('Código')
                ->maxLength(30),

            Forms\Components\Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListCalibers::route('/'),
            'create' => Pages\CreateCaliber::route('/create'),
            'edit' => Pages\EditCaliber::route('/{record}/edit'),
        ];
    }
}
