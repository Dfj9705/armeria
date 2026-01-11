<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $nit = trim((string) ($data['nit'] ?? ''));
        $cui = trim((string) ($data['cui'] ?? ''));

        // ✅ NIT o CUI requerido
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

        $id = $this->record->id;

        // ✅ Duplicados NIT ignorando el mismo cliente
        if ($nit !== '' && Customer::where('nit', $nit)->where('id', '!=', $id)->exists()) {
            Notification::make()
                ->title('Datos incompletos')
                ->body('Ya existe otro cliente con este NIT.')
                ->danger()
                ->send();
            throw ValidationException::withMessages([
                'data.nit' => 'Ya existe otro cliente con este NIT.',
            ]);
        }

        // ✅ Duplicados CUI ignorando el mismo cliente
        if ($cui !== '' && Customer::where('cui', $cui)->where('id', '!=', $id)->exists()) {
            Notification::make()
                ->title('Datos incompletos')
                ->body('Ya existe otro cliente con este CUI.')
                ->danger()
                ->send();
            throw ValidationException::withMessages([
                'data.cui' => 'Ya existe otro cliente con este CUI.',
            ]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
