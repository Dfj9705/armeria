<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $nit = trim((string) ($data['nit'] ?? ''));
        $cui = trim((string) ($data['cui'] ?? ''));

        if ($nit === '' && $cui === '') {

            Notification::make()
                ->title('Datos incompletos')
                ->body('Debes ingresar NIT o CUI.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'data.nit' => 'Debes ingresar NIT o CUI.',
                'data.cui' => 'Debes ingresar NIT o CUI.',
            ]);
        }

        if ($nit !== '' && Customer::where('nit', $nit)->exists()) {
            Notification::make()
                ->title('Datos incompletos')
                ->body('Ya existe un cliente con este NIT.')
                ->danger()
                ->send();
            throw ValidationException::withMessages([
                'data.nit' => 'Ya existe un cliente con este NIT.',
            ]);
        }

        // ✅ Duplicados (CUI)
        if ($cui !== '' && Customer::where('cui', $cui)->exists()) {
            Notification::make()
                ->title('Datos incompletos')
                ->body('Ya existe un cliente con este CUI.')
                ->danger()
                ->send();
            throw ValidationException::withMessages([
                'data.cui' => 'Ya existe un cliente con este CUI.',
            ]);
        }


        return $data;
    }
}
