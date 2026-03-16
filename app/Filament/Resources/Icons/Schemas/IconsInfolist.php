<?php

namespace App\Filament\Resources\Icons\Schemas;

use Filament\Schemas\Schema;

class IconsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                dd('test 1 ')
            ]);
    }
}
