<?php

namespace App\Filament\Resources\AmmoResource\Pages;

use App\Filament\Resources\AmmoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmmo extends EditRecord
{
    protected static string $resource = AmmoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
