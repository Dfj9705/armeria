<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Si no vienen, forzamos 0.00
        $data['subtotal'] = (float) ($data['subtotal'] ?? 0);
        $data['tax'] = (float) ($data['tax'] ?? 0);
        $data['total'] = (float) ($data['total'] ?? 0);

        return $data;
    }
}
