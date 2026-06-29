<?php

namespace App\Filament\Resources\AccessoryResource\RelationManagers;

use Auth;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';
    protected static ?string $title = 'Movimientos';
    protected static ?string $recordTitleAttribute = 'id';


    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('branch_id')
                ->default(fn() => auth()->user()->branch_id),
            Forms\Components\Hidden::make('type')
                ->required(),

            Forms\Components\TextInput::make('quantity')
                ->label('Cantidad')
                ->numeric()
                ->minValue(1)
                ->required(),

            Forms\Components\TextInput::make('unit_cost')
                ->label('Costo unitario (opcional)')
                ->numeric()
                ->visible(fn(Forms\Get $get) => $get('type') === 'in'),

            Forms\Components\DateTimePicker::make('occurred_at')
                ->label('Fecha y hora')
                ->required()
                ->default(now()),

            Forms\Components\TextInput::make('reference')
                ->label('Referencia')
                ->maxLength(120),

            Forms\Components\Textarea::make('notes')
                ->label('Observaciones')
                ->rows(3),

            Forms\Components\Hidden::make('user_id')
                ->default(fn() => \Illuminate\Support\Facades\Auth::id()),
        ]);
    }

    public function table(Table $table): Table
    {
        /** @var Accessory $accessory */
        $accessory = $this->getOwnerRecord();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('occurred_at')->label('Fecha')->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn(string $state) => $state === 'in' ? 'Ingreso' : 'Egreso')
                    ->color(fn(string $state) => $state === 'in' ? 'success' : 'danger')
                    ->badge(),
                Tables\Columns\TextColumn::make('quantity')->label('Cantidad')->sortable(),
                Tables\Columns\TextColumn::make('unit_cost')->label('Costo')->money('GTQ')->toggleable(),
                Tables\Columns\TextColumn::make('reference')->label('Referencia')->toggleable(),
                Tables\Columns\TextColumn::make('user.name')->label('Usuario')->toggleable(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();

                if ($user->isSuperAdmin()) {
                    return $query;
                }

                return $query->where('branch_id', $user->branch_id);
            })
            ->headerActions([
                Tables\Actions\CreateAction::make('ingreso')
                    ->label('Ingreso')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->modalHeading('Ingreso de accesorio')
                    ->mountUsing(function (Forms\ComponentContainer $form) {
                        $form->fill([
                            'type' => 'in',
                            'occurred_at' => now(),
                            'branch_id' => auth()->user()->branch_id,
                        ]);
                    })
                    ->mutateFormDataUsing(function (array $data) {
                        $data['branch_id'] = auth()->user()->branch_id;
                        return $data;
                    }),

                Tables\Actions\CreateAction::make('egreso')
                    ->label('Egreso')
                    ->icon('heroicon-o-minus')
                    ->color('danger')
                    ->modalHeading('Egreso de accesorio')
                    ->mountUsing(function (Forms\ComponentContainer $form) {
                        $form->fill([
                            'type' => 'out',
                            'occurred_at' => now(),
                            'branch_id' => auth()->user()->branch_id,
                        ]);
                    })
                    ->mutateFormDataUsing(function (array $data) {
                        $data['branch_id'] = auth()->user()->branch_id;
                        return $data;
                    })
                    ->before(function (array $data) {
                        $stock = $this->getOwnerRecord()
                            ->movements()
                            ->where('branch_id', auth()->user()->branch_id)
                            ->selectRaw("
        COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END), 0) as stock
    ")
                            ->value('stock');

                        if ((int) $data['quantity'] > (int) $stock) {
                            Notification::make()
                                ->title('Stock insuficiente')
                                ->body("Stock actual: {$stock}. No puedes egresar {$data['quantity']}.")
                                ->danger()
                                ->send();

                            // Esto evita que se guarde
                        }
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        // Evitar que cambien el tipo al editar
                        unset($data['type']);
                        return $data;
                    })
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Costo unitario (opcional)')
                            ->numeric(),
                        Forms\Components\DateTimePicker::make('occurred_at')
                            ->label('Fecha y hora')
                            ->required(),
                        Forms\Components\TextInput::make('reference')
                            ->label('Referencia')
                            ->maxLength(120),
                        Forms\Components\Textarea::make('notes')
                            ->label('Observaciones')
                            ->rows(3),
                    ]),

                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('occurred_at', 'asc');
    }
}
