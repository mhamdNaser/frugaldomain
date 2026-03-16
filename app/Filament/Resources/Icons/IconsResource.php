<?php

namespace App\Filament\Resources\Icons;

use App\Filament\Resources\Icons\Pages\CreateIcons;
use App\Filament\Resources\Icons\Pages\EditIcons;
use App\Filament\Resources\Icons\Pages\ListIcons;
use App\Filament\Resources\Icons\Pages\ViewIcons;
use App\Filament\Resources\Icons\Schemas\IconsForm;
use App\Filament\Resources\Icons\Schemas\IconsInfolist;
use App\Filament\Resources\Icons\Tables\IconsTable;
use App\Modules\Icon\Models\Icon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IconsResource extends Resource
{
    protected static ?string $model = Icon::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $recordTitleAttribute = 'Icon';

    public static function form(Schema $schema): Schema
    {
        return IconsForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return IconsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IconsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIcons::route('/'),
            'create' => CreateIcons::route('/create'),
            'view' => ViewIcons::route('/{record}'),
            'edit' => EditIcons::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
