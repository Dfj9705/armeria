<?php

namespace App\Filament\Resources\AmmoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ammo')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ammo')
            ->columns([
                Tables\Columns\TextColumn::make('moved_at')
                    ->label('Fecha/Hora')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state === 'IN' ? 'Ingreso' : 'Egreso')
                    ->color(fn($state) => $state === 'IN' ? 'success' : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('boxes')
                    ->label('Cajas')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit_cost_box')
                    ->label('Costo/caja')
                    ->money('GTQ', true)
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('rounds')
                    ->label('Suelta')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_rounds')
                    ->label('Total cartuchos')
                    ->state(function ($record) {
                        $ammo = $this->getOwnerRecord();
                        $rpb = (int) ($ammo->rounds_per_box ?? 0);
                        $boxes = (int) ($record->boxes ?? 0);
                        $rounds = (int) ($record->rounds ?? 0);
                        return ($boxes * $rpb) + $rounds;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Referencia')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'IN' => 'Ingreso',
                        'OUT' => 'Egreso',
                    ]),
            ])
            ->headerActions([
                CreateAction::make('ingreso')
                    ->label('Ingreso cajas')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Ingreso de munición (cajas)')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['type'] = 'IN';
                        $data['user_id'] = Auth::id();
                        $data['moved_at'] = $data['moved_at'] ?? now();
                        return $data;
                    })
                    ->form([
                        Forms\Components\TextInput::make('boxes')
                            ->label('Cajas')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('unit_cost_box')
                            ->label('Costo por caja')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Q')
                            ->nullable(),

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
                    ->label('Egreso cajas')
                    ->icon('heroicon-o-minus')
                    ->color('danger')
                    ->modalHeading('Egreso de munición (cajas)')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['type'] = 'OUT';
                        $data['user_id'] = Auth::id();
                        $data['moved_at'] = $data['moved_at'] ?? now();
                        return $data;
                    })
                    ->form([
                        Forms\Components\TextInput::make('boxes')
                            ->label('Cajas')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('reference')
                            ->label('Referencia')
                            ->required()
                            ->maxLength(150),

                        Forms\Components\DateTimePicker::make('moved_at')
                            ->label('Fecha/Hora')
                            ->default(now())
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ])
                    ->before(function (array $data) {
                        // ✅ Validación de stock antes de crear egreso
                        $ammo = $this->getOwnerRecord(); // Ammo
                        $available = $ammo->stock_boxes;

                        if ((int) $data['boxes'] > $available) {
                            Notification::make()
                                ->title('Stock insuficiente')
                                ->body("Disponible: {$available} cajas.")
                                ->danger()
                                ->send();

                            // Detiene la acción
                            $this->halt();
                        }
                    }),

                CreateAction::make('egreso_suelto')
                    ->label('Egreso suelto')
                    ->icon('heroicon-o-scissors')
                    ->color('warning')
                    ->modalHeading('Egreso de munición suelta (cartuchos)')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['type'] = 'OUT';
                        $data['boxes'] = null; // suelta
                        $data['rounds'] = $data['rounds'] ?? 0;
                        $data['user_id'] = Auth::id();
                        $data['moved_at'] = $data['moved_at'] ?? now();
                        return $data;
                    })
                    ->form([
                        Forms\Components\TextInput::make('rounds')
                            ->label('Cartuchos')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('reference')
                            ->label('Referencia')
                            ->required()
                            ->maxLength(150),

                        Forms\Components\DateTimePicker::make('moved_at')
                            ->label('Fecha/Hora')
                            ->default(now())
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->before(function (array $data) {
                        $ammo = $this->getOwnerRecord(); // Ammo
                        $availableRounds = (int) $ammo->stock_rounds;

                        if ((int) $data['rounds'] > $availableRounds) {
                            Notification::make()
                                ->title('Stock insuficiente')
                                ->body("Disponible: {$availableRounds} cartuchos.")
                                ->danger()
                                ->send();

                            $this->halt();
                        }
                    }),

                CreateAction::make('ingreso_suelto')
                    ->label('Ingreso suelto')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->modalHeading('Ingreso de munición suelta (cartuchos)')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['type'] = 'IN';
                        $data['boxes'] = null;
                        $data['rounds'] = $data['rounds'] ?? 0;
                        $data['user_id'] = Auth::id();
                        $data['moved_at'] = $data['moved_at'] ?? now();
                        return $data;
                    })
                    ->form([
                        Forms\Components\TextInput::make('rounds')
                            ->label('Cartuchos')
                            ->numeric()
                            ->minValue(1)
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
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make('ingreso')
                    ->label('Ingreso')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Ingreso de munición (cajas)')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['type'] = 'IN';
                        $data['user_id'] = Auth::id();
                        $data['moved_at'] = $data['moved_at'] ?? now();
                        return $data;
                    })
                    ->form([
                        Forms\Components\TextInput::make('boxes')
                            ->label('Cajas')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('unit_cost_box')
                            ->label('Costo por caja')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Q')
                            ->nullable(),

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
            ]);
    }
}
