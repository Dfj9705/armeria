<?php

namespace App\Filament\Resources\WeaponResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\CreateAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                Tables\Columns\TextColumn::make('moved_at')->label('Fecha')->dateTime(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge(),
                Tables\Columns\TextColumn::make('quantity')->label('Cantidad'),
                Tables\Columns\TextColumn::make('unit_cost')->label('Costo')->money('GTQ', true),
                Tables\Columns\TextColumn::make('reference')->label('Referencia'),
            ])
            ->headerActions([
                CreateAction::make('ingreso')
                    ->label('Ingreso')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Ingreso de stock')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['type'] = 'IN';
                        $data['moved_at'] = $data['moved_at'] ?? now();
                        $data['user_id'] = Auth::id();
                        return $data;
                    })
                    ->form([
                        Forms\Components\Hidden::make('type')->default('IN'),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Costo unitario')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Q')
                            ->required(),

                        Forms\Components\TextInput::make('reference')
                            ->label('Referencia')
                            ->maxLength(150),

                        Forms\Components\DateTimePicker::make('moved_at')
                            ->label('Fecha/Hora')
                            ->default(now())
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ]),

                CreateAction::make('egreso')
                    ->label('Egreso')
                    ->icon('heroicon-o-minus')
                    ->color('danger')
                    ->modalHeading('Egreso de stock')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['type'] = 'OUT';
                        $data['moved_at'] = $data['moved_at'] ?? now();
                        $data['user_id'] = Auth::id();
                        return $data;
                    })
                    ->before(function (array $data, RelationManager $livewire) {
                        $weapon = $livewire->ownerRecord; // arma actual
                        $qty = (int) ($data['quantity'] ?? 0);

                        if ($qty > $weapon->stock) {
                            throw ValidationException::withMessages([
                                'quantity' => "No hay stock suficiente. Disponible: {$weapon->stock}",
                            ]);
                        }
                    })
                    ->form([
                        Forms\Components\Hidden::make('type')->default('OUT'),

                        Forms\Components\Placeholder::make('stock_actual')
                            ->label('Stock actual')
                            ->content(fn($record) => $record?->stock ?? 0)
                            ->extraAttributes(['class' => 'text-lg font-bold']),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('reference')
                            ->label('Referencia')
                            ->maxLength(150)
                            ->required(),

                        Forms\Components\DateTimePicker::make('moved_at')
                            ->label('Fecha/Hora')
                            ->default(now())
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ]),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
