<?php

namespace App\Filament\Resources\Icons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class IconsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('png_preview')
                    ->label('PNG')
                    ->getStateUsing(fn ($record) =>
                        ($p = optional($record->files->firstWhere('file_type', 'png'))->file_path)
                            ? asset($p)
                            : null
                    )
                    ->width(40),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('Uploaded By')
                    ->toggleable(),
                TextColumn::make('is_premium')
                    ->label('Premium')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Premium' : 'Free')
                    ->color(fn($state) => $state ? 'danger' : 'success'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('download_count')
                    ->sortable()
                    ->label('Downloads'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
