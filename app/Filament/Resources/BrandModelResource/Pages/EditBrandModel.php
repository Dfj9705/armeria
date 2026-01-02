<?php

namespace App\Filament\Resources\BrandModelResource\Pages;

use App\Filament\Resources\BrandModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBrandModel extends EditRecord
{
    protected static string $resource = BrandModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
