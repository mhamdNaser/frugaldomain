<?php

namespace App\Filament\Resources\Stores\Tables;

use Dom\Text;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class StoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(25),
                TextColumn::make('shopify_domain')
                    ->label('Domain')
                    ->searchable()
                    ->copyable()
                    ->limit(30),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('currency')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('plan')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'free' => 'gray',
                        'pro' => 'primary',
                        'enterprise' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'active' => 'success',
                        'suspended' => 'danger',
                        'uninstalled' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('installed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('last_synced_at')
                    ->since()
                    ->label('Last Sync')
                    ->sortable(),
                IconColumn::make('uninstalled_at')
                    ->label('Installed')
                    ->boolean(fn($record) => is_null($record->uninstalled_at)),
                TextColumn::make('created_at')
                    ->dateTime()
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
