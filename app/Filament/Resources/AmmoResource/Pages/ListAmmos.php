<?php

namespace App\Filament\Resources\AmmoResource\Pages;

use App\Filament\Resources\AmmoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmmos extends ListRecords
{
    protected static string $resource = AmmoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
