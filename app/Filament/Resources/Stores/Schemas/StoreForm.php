<?php

namespace App\Filament\Resources\Stores\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Store Info')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('shopify_domain')
                            ->label('Shopify Domain')
                            ->placeholder('example.myshopify.com')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('shopify_store_id')
                            ->label('Shopify Store ID')
                            ->maxLength(255)
                            ->nullable(),

                        TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->nullable(),

                        TextInput::make('currency')
                            ->maxLength(10)
                            ->nullable(),

                        TextInput::make('timezone')
                            ->maxLength(255)
                            ->nullable(),

                        Select::make('plan')
                            ->options([
                                'free' => 'Free',
                                'pro' => 'Pro',
                                'enterprise' => 'Enterprise',
                            ])
                            ->default('free')
                            ->required(),

                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'uninstalled' => 'Uninstalled',
                            ])
                            ->default('active')
                            ->required(),

                        DateTimePicker::make('installed_at')
                            ->nullable(),

                        DateTimePicker::make('last_synced_at')
                            ->nullable(),
                    ]),
            ]);
    }
}
