<?php

namespace App\Filament\Resources\WeaponResource\RelationManagers;

use App\Models\WeaponUnit;
use App\Models\WeaponUnitMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'units';
    public function table(Table $table): Table
    {
        return $table
            ->heading('Movimientos')
            ->recordTitleAttribute('serial_number')
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('No. Serie')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state === 'IN_STOCK' ? 'EN STOCK' : 'SALIDA')
                    ->color(fn($state) => $state === 'IN_STOCK' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('purchase_cost')
                    ->label('Costo')
                    ->money('GTQ', true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ingreso')
                    ->dateTime(),
            ])
            ->headerActions([
                Action::make('ingreso')
                    ->label('Ingreso')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Ingreso de unidades (seriales)')
                    ->form([
                        Forms\Components\Textarea::make('serials')
                            ->label('Seriales (uno por línea)')
                            ->rows(10)
                            ->placeholder("ABC123\nDEF456\nGHI789")
                            ->required(),

                        Forms\Components\TextInput::make('purchase_cost')
                            ->label('Costo unitario (opcional)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Q')
                            ->nullable(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->action(function (array $data): void {
                        $weapon = $this->getOwnerRecord();

                        // 1) Parsear seriales
                        $lines = preg_split('/\r\n|\r|\n/', trim($data['serials'] ?? ''));
                        $serials = collect($lines)
                            ->map(fn($s) => trim($s))
                            ->filter()
                            ->unique()
                            ->values();

                        if ($serials->isEmpty()) {
                            Notification::make()
                                ->title('No ingresaste seriales válidos.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // 2) Validar duplicados en BD (global, porque serial es unique)
                        $existing = WeaponUnit::query()
                            ->whereIn('serial_number', $serials->all())
                            ->pluck('serial_number')
                            ->all();

                        if (!empty($existing)) {
                            Notification::make()
                                ->title('Algunos seriales ya existen en el sistema.')
                                ->body("Duplicados: " . implode(', ', $existing))
                                ->danger()
                                ->send();
                            return;
                        }

                        // 3) Insert masivo en transacción
                        DB::transaction(function () use ($weapon, $serials, $data) {
                            $now = now();

                            foreach ($serials as $serial) {
                                $unit = $weapon->units()->create([
                                    'serial_number' => $serial,
                                    'status' => 'IN_STOCK',
                                    'purchase_cost' => $data['purchase_cost'] ?? null,
                                    'notes' => $data['notes'] ?? null,
                                ]);

                                $unit->movements()->create([
                                    'type' => 'IN',
                                    'reference' => $data['reference'] ?? null,
                                    'notes' => $data['notes'] ?? null,
                                    'moved_at' => $data['moved_at'] ?? $now,
                                    'user_id' => auth()->id(),
                                ]);
                            }
                        });


                        Notification::make()
                            ->title('Ingreso registrado')
                            ->body('Unidades ingresadas: ' . $serials->count())
                            ->success()
                            ->send();
                    }),
                Action::make('egreso_scanner')
                    ->label('Egreso (scanner)')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->color('danger')
                    ->modalHeading('Egreso de unidades (seriales)')
                    ->form([
                        Forms\Components\Textarea::make('serials')
                            ->label('Seriales (uno por línea)')
                            ->rows(10)
                            ->placeholder("ABC123\nDEF456\nGHI789")
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
                            ->rows(3),
                    ])
                    ->action(function (array $data): void {
                        $weapon = $this->getOwnerRecord();

                        // 1) Parsear seriales
                        $lines = preg_split('/\r\n|\r|\n/', trim($data['serials'] ?? ''));
                        $serials = collect($lines)
                            ->map(fn($s) => trim($s))
                            ->filter()
                            ->unique()
                            ->values();

                        if ($serials->isEmpty()) {
                            Notification::make()
                                ->title('No se ingresaron seriales válidos.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // 2) Buscar unidades de ESTE arma
                        $units = $weapon->units()
                            ->whereIn('serial_number', $serials->all())
                            ->get()
                            ->keyBy('serial_number');

                        $errors = [];

                        foreach ($serials as $serial) {
                            if (!$units->has($serial)) {
                                $errors[] = "No existe o no pertenece a este arma: {$serial}";
                            } elseif ($units[$serial]->status !== 'IN_STOCK') {
                                $errors[] = "No disponible en stock: {$serial}";
                            }
                        }

                        if (!empty($errors)) {
                            Notification::make()
                                ->title('Errores en el egreso')
                                ->body(implode("\n", $errors))
                                ->danger()
                                ->send();
                            return;
                        }

                        // 3) Procesar egreso
                        DB::transaction(function () use ($units, $data) {
                            foreach ($units as $unit) {
                                $unit->update(['status' => 'OUT']);

                                $unit->movements()->create([
                                    'type' => 'OUT',
                                    'reference' => $data['reference'],
                                    'notes' => $data['notes'] ?? null,
                                    'moved_at' => $data['moved_at'] ?? now(),
                                    'user_id' => Auth::id(),
                                ]);
                            }
                        });

                        Notification::make()
                            ->title('Egreso registrado')
                            ->body('Unidades egresadas: ' . $units->count())
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('salida_masiva')
                        ->label('Salida (seleccionadas)')
                        ->icon('heroicon-o-arrow-up-on-square')
                        ->color('danger')
                        ->form([
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
                                ->rows(3),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            // validar que todas estén disponibles
                            $notAvailable = $records->filter(fn($r) => $r->status !== 'IN_STOCK');

                            if ($notAvailable->isNotEmpty()) {
                                Notification::make()
                                    ->title('Algunas unidades no están disponibles en stock.')
                                    ->body('Series: ' . $notAvailable->pluck('serial_number')->implode(', '))
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // salida masiva
                            foreach ($records as $record) {
                                $record->update([
                                    'status' => 'OUT',
                                    'notes' => trim(($record->notes ?? '') . "\n" . ($data['notes'] ?? '')) ?: $record->notes,
                                ]);

                                $record->movements()->create([
                                    'type' => 'OUT',
                                    'reference' => $data['reference'],
                                    'notes' => $data['notes'],
                                    'moved_at' => now(),
                                    'user_id' => auth()->id(),
                                ]);
                                // (Opcional a futuro) registrar movimiento OUT
                            }

                            Notification::make()
                                ->title('Salida masiva registrada')
                                ->body('Unidades: ' . $records->count())
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('reingresar_masivo')
                        ->label('Reingresar seleccionadas')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->form([
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
                                ->rows(3),
                        ])
                        ->action(function (Collection $records, array $data): void {

                            $out = $records->filter(fn($r) => $r->status === 'OUT');
                            $notOut = $records->reject(fn($r) => $r->status === 'OUT');

                            // Si seleccionó mixto, avisamos (pero reingresamos las OUT)
                            if ($notOut->isNotEmpty()) {
                                Notification::make()
                                    ->title('Algunas seleccionadas no están en salida')
                                    ->body('Se ignoraron: ' . $notOut->pluck('serial_number')->implode(', '))
                                    ->warning()
                                    ->send();
                            }

                            if ($out->isEmpty()) {
                                Notification::make()
                                    ->title('No hay unidades en salida para reingresar')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            foreach ($out as $record) {
                                $record->update(['status' => 'IN_STOCK']);

                                $record->movements()->create([
                                    'type' => 'IN',
                                    'reference' => $data['reference'],
                                    'notes' => $data['notes'] ?? null,
                                    'moved_at' => $data['moved_at'] ?? now(),
                                    'user_id' => Auth::id(),
                                ]);
                            }

                            Notification::make()
                                ->title('Reingreso masivo realizado')
                                ->body('Unidades reingresadas: ' . $out->count())
                                ->success()
                                ->send();
                        }),
                ])
            ])
            ->actions([
                Action::make('salida')
                    ->label('Salida')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'IN_STOCK')
                    ->modalHeading(fn($record) => 'Salida - Serie: ' . $record->serial_number)
                    ->form([
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
                            ->rows(3),
                    ])
                    ->action(function (array $data, $record): void {
                        // refresca el record por seguridad
                        $record->refresh();

                        if ($record->status !== 'IN_STOCK') {
                            Notification::make()
                                ->title('Esta unidad ya no está disponible en stock.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update([
                            'status' => 'OUT',
                            'notes' => trim(($record->notes ?? '') . "\n" . ($data['notes'] ?? '')) ?: $record->notes,
                        ]);

                        // (Opcional a futuro) aquí registrarías movimiento OUT en weapon_unit_movements
                        $record->movements()->create([
                            'type' => 'OUT',
                            'reference' => $data['reference'],
                            'notes' => $data['notes'],
                            'moved_at' => now(),
                            'user_id' => auth()->id(),
                        ]);
                        Notification::make()
                            ->title('Salida registrada')
                            ->success()
                            ->send();
                    }),

                Action::make('reingresar')
                    ->label('Reingresar')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'OUT')
                    ->modalHeading(fn($record) => 'Reingreso - Serie: ' . $record->serial_number)
                    ->form([
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
                            ->rows(3),
                    ])
                    ->action(function (array $data, $record): void {
                        $record->refresh();

                        if ($record->status !== 'OUT') {
                            Notification::make()
                                ->title('Esta unidad no está en estado de salida.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update([
                            'status' => 'IN_STOCK',
                        ]);

                        // ✅ Si ya tienes weapon_unit_movements:
                        $record->movements()->create([
                            'type' => 'IN',
                            'reference' => $data['reference'],
                            'notes' => $data['notes'] ?? null,
                            'moved_at' => $data['moved_at'] ?? now(),
                            'user_id' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Unidad reingresada a stock')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
