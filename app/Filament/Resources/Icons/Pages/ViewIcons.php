<?php

namespace App\Filament\Resources\Icons\Pages;

use App\Filament\Resources\Icons\IconsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIcons extends ViewRecord
{
    protected static string $resource = IconsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
