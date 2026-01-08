<?php

namespace App\Filament\Resources\AccessoryCategoryResource\Pages;

use App\Filament\Resources\AccessoryCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccessoryCategories extends ListRecords
{
    protected static string $resource = AccessoryCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
