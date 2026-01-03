<?php

namespace App\Filament\Resources\CaliberResource\Pages;

use App\Filament\Resources\CaliberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCaliber extends EditRecord
{
    protected static string $resource = CaliberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
