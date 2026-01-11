<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\Municipality;
use App\Services\Tekra\TekraContribuyenteService;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([


                Section::make('Identificación')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nit')
                            ->label('NIT')
                            ->helperText('Con o sin guiones. Ej: 1234567-8')
                            ->maxLength(20)
                            ->dehydrateStateUsing(fn($state) => self::cleanId($state))
                            ->rules([
                                'nullable',
                                'max:20',
                            ]),

                        TextInput::make('cui')
                            ->label('CUI')
                            ->helperText('Con o sin espacios/guiones. Ej: 1234 56789 0101')
                            ->maxLength(20)
                            ->dehydrateStateUsing(fn($state) => self::cleanId($state))
                            ->rules([
                                'nullable',
                                'max:20',

                            ]),

                        Actions::make([
                            Action::make('tekra_lookup')
                                ->label('Consultar SAT')
                                ->icon('heroicon-o-magnifying-glass')
                                ->action(function (Get $get, Set $set) {
                                    $nit = (string) $get('nit');
                                    $cui = (string) $get('cui');

                                    $nit = preg_replace('/[\s-]/', '', trim($nit));
                                    $cui = preg_replace('/[\s-]/', '', trim($cui));

                                    if ($nit === '' && $cui === '') {
                                        Notification::make()
                                            ->title('Ingresa NIT o CUI')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $svc = app(TekraContribuyenteService::class);

                                    $response = $nit !== ''
                                        ? $svc->consultaNit($nit)
                                        : $svc->consultaCui($cui);

                                    $error = data_get($response, 'resultado.0.error');
                                    $mensaje = data_get($response, 'resultado.0.mensaje');

                                    if ((int) $error !== 0) {
                                        Notification::make()
                                            ->title('TEKRA: error')
                                            ->body($mensaje ?: 'No se pudo consultar.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $datos = data_get($response, 'datos.0', []);

                                    // NIT
                                    if ($nit !== '') {
                                        $set('nit', data_get($datos, 'nit', $nit));
                                    }

                                    // Nombre (TEKRA lo trae con comas tipo: "APELLIDOS,NOMBRES")
                                    $nombreTekra = (string) (data_get($datos, 'nombre') ?? data_get($datos, 'nombre_completo') ?? '');
                                    $nombreLimpio = trim(preg_replace('/\s+/', ' ', str_replace(',', ' ', $nombreTekra)));

                                    if ($nombreLimpio !== '') {
                                        // Puedes decidir: name = nombre limpio, tax_name = nombre tekra
                                        $set('tax_name', $nombreLimpio);

                                        // si name está vacío, lo llenamos también
                                        if (trim((string) $get('name')) === '') {
                                            $set('name', $nombreLimpio);
                                        }
                                    }

                                    $direccion = data_get($datos, 'direccion_completa');
                                    if (!empty($direccion) && trim((string) $get('address')) === '') {
                                        $set('address', $direccion);
                                    }

                                    Notification::make()
                                        ->title('Datos cargados desde SAT')
                                        ->success()
                                        ->send();
                                }),
                        ])->columnSpanFull()
                    ])
                    ->afterStateHydrated(function (Forms\Get $get, Forms\Set $set) {
                        // (opcional) aquí podrías normalizar en UI si quisieras
                    }),
                Section::make('Datos del cliente')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(150),

                        TextInput::make('tax_name')
                            ->label('Nombre para factura (FEL)')
                            ->helperText('Si no lo llenas, se usa el “Nombre”.')
                            ->maxLength(150),

                        TextInput::make('email')
                            ->label('Correo')
                            ->email()
                            ->maxLength(150),

                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(50),

                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ]),


                Section::make('Dirección')
                    ->columns(2)
                    ->schema([
                        Textarea::make('address')
                            ->label('Dirección')
                            ->rows(2)
                            ->columnSpanFull()
                            ->maxLength(255),
                        Select::make('department_id')
                            ->label('Departamento')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('municipality_id', null)),

                        Select::make('municipality_id')
                            ->label('Municipio')
                            ->key(fn(Get $get) => 'brand-model-' . ($get('brand_id') ?? 'none'))
                            ->options(function (Get $get) {
                                $departmentId = $get('department_id');
                                if (!$departmentId)
                                    return [];

                                return Municipality::query()
                                    ->where('department_id', $departmentId)
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->live()
                            ->disabled(fn(Get $get) => blank($get('department_id')))
                            ->placeholder('Seleccione un municipio'),
                    ]),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tax_name')
                    ->label('Nombre FEL')
                    ->toggleable()
                    ->wrap(),

                TextColumn::make('nit')
                    ->label('NIT')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('cui')
                    ->label('CUI')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('department.name')
                    ->label('Departamento')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('municipality.name')
                    ->label('Municipio')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Activo'),

                SelectFilter::make('department_id')
                    ->label('Departamento')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    private static function cleanId(?string $value): ?string
    {
        if ($value === null)
            return null;
        $value = trim($value);
        if ($value === '')
            return null;

        // Quita espacios y guiones para guardar consistente
        return preg_replace('/[\s-]/', '', $value);
    }
}
