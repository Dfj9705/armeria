<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Services\Sales\ConfirmSale;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['subtotal'] = (float) ($data['subtotal'] ?? 0);
        $data['tax'] = (float) ($data['tax'] ?? 0);
        $data['total'] = (float) ($data['total'] ?? 0);

        return $data;
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Confirmar venta')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn() => $this->record->status === 'draft')
                ->action(function () {
                    try {
                        app(ConfirmSale::class)->handle($this->record, auth()->id());

                        Notification::make()
                            ->title('Venta confirmada')
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'subtotal',
                            'tax',
                            'total',
                            'confirmed_at',
                        ]);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('No se pudo confirmar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getFormActions(): array
    {
        if ($this->record->status !== 'draft') {
            return []; // sin botones (ni guardar)
        }

        return parent::getFormActions();
    }

    protected function beforeSave(): void
    {
        if ($this->record->status !== 'draft') {
            abort(403, 'No puedes modificar una venta confirmada.');
        }
    }
}
