<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Notification;

class PaymentsRelationManager extends RelationManager
{

    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pagos';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DateTimePicker::make('date')
                ->label('Fecha')
                ->required()
                ->default(now()),

            Forms\Components\TextInput::make('amount')
                ->label('Monto')
                ->numeric()
                ->prefix('Q')
                ->required()
                ->minValue(0.01)
                ->rule(function () {

                    $sale = $this->ownerRecord;

                    return function ($attribute, $value, $fail) use ($sale) {

                        $pending = $sale->pending_amount;

                        if ($value > $pending) {
                            $fail(
                                'El pago excede el saldo pendiente de Q' .
                                number_format($pending, 2)
                            );
                        }
                    };
                }),

            Forms\Components\Select::make('method')
                ->label('Método')
                ->options([
                    'cash' => 'Efectivo',
                    'transfer' => 'Transferencia',
                    'card' => 'Tarjeta',
                    'check' => 'Cheque',
                ])
                ->required(),

            Forms\Components\TextInput::make('reference')
                ->label('Referencia')
                ->maxLength(100),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('GTQ'),

                Tables\Columns\BadgeColumn::make('method')
                    ->label('Método')
                    ->colors([
                        'success' => 'cash',
                        'primary' => 'transfer',
                        'warning' => 'card',
                        'gray' => 'check',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'cash' => 'Efectivo',
                        'transfer' => 'Transferencia',
                        'card' => 'Tarjeta',
                        'check' => 'Cheque',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Referencia'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Pago')
                    ->before(function (array $data) {

                        $venta = $this->getOwnerRecord();

                        $pagado = (float) $venta->total_paid;
                        $saldo = (float) $venta->total - $pagado;

                        if ((float) $data['amount'] > $saldo) {
                            Notification::make()
                                ->title('El monto excede el saldo pendiente.')
                                ->danger()
                                ->send();
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'amount' => 'El monto excede el saldo pendiente.',
                            ]);
                        }
                    })
                    ->after(function ($record) {
                        $record->refresh();

                        $this->dispatch('refreshSaleTotals');
                    })
                    // Solo permitir pagos cuando no es draft/cancelled:
                    ->visible(fn() => in_array($this->getOwnerRecord()->status, ['confirmed', 'certified'], true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => in_array($this->getOwnerRecord()->status, ['confirmed', 'certified'], true))
                    ->after(function ($record) {

                        $this->dispatch('refreshSaleTotals');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => in_array($this->getOwnerRecord()->status, ['confirmed', 'certified', 'cancelled'], true))
                    ->recordTitle(fn($record) => "Eliminar pago Q{$record->amount}")
                    ->after(function ($record) {

                        $this->dispatch('refreshSaleTotals');
                    }),
            ]);
    }

}
