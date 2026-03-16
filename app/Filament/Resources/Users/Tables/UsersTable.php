<?php

namespace App\Filament\Resources\Users\Tables;

use Dom\Text;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(20),
                TextColumn::make('first_name')
                    ->label('First Name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('medium_name')
                    ->label('Middle Name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_name')
                    ->label('Last Name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->limit(30),
                TextColumn::make('phone')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('country.name')
                    ->label('Country')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('state.name')
                    ->label('State')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('city.name')
                    ->label('City')
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('image')
                    ->label('Avatar')
                    ->circular()
                    ->width(40)
                    ->defaultImageUrl(asset('images/default-avatar.png')),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean(fn($record) => ! is_null($record->email_verified_at)),
                IconColumn::make('status')
                    ->boolean()
                    ->label('Status'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
