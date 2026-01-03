<?php

namespace App\Filament\Resources\CaliberResource\Pages;

use App\Filament\Resources\CaliberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCalibers extends ListRecords
{
    protected static string $resource = CaliberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
