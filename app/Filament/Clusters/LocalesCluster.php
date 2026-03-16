<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class LocalesCluster extends Cluster
{
    protected static ?string $navigationLabel = 'Locales';
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedLanguage;
}
