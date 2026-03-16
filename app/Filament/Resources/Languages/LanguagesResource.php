<?php

namespace App\Filament\Resources\Languages;

use App\Filament\Resources\Languages\Pages\CreateLanguages;
use App\Filament\Resources\Languages\Pages\EditLanguages;
use App\Filament\Resources\Languages\Pages\ListLanguages;
use App\Filament\Resources\Languages\Pages\ViewLanguages;
use App\Filament\Resources\Languages\Schemas\LanguagesForm;
use App\Filament\Resources\Languages\Schemas\LanguagesInfolist;
use App\Filament\Resources\Languages\Tables\LanguagesTable;
use App\Modules\Locale\Models\Language;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LanguagesResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $recordTitleAttribute = 'Language';

    public static function form(Schema $schema): Schema
    {
        return LanguagesForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LanguagesInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LanguagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLanguages::route('/'),
            'create' => CreateLanguages::route('/create'),
            'view' => ViewLanguages::route('/{record}'),
            'edit' => EditLanguages::route('/{record}/edit'),
        ];
    }
}
