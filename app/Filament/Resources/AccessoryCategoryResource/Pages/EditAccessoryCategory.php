<?php

namespace App\Filament\Resources\AccessoryCategoryResource\Pages;

use App\Filament\Resources\AccessoryCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccessoryCategory extends EditRecord
{
    protected static string $resource = AccessoryCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
