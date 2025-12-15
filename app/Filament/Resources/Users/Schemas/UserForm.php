<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                TextInput::make('username')
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                Textarea::make('bio')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('avatar')
                    ->default(null),
                TextInput::make('cover_photo')
                    ->default(null),
                DatePicker::make('date_of_birth'),
                Select::make('gender')
                    ->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'])
                    ->default(null),
                TextInput::make('location')
                    ->default(null),
                Toggle::make('is_verified')
                    ->required(),
                Toggle::make('is_creator')
                    ->required(),
                Toggle::make('is_admin')
                    ->required(),
                DateTimePicker::make('creator_approved_at'),
                Toggle::make('is_banned')
                    ->required(),
                Textarea::make('banned_reason')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_earned')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('followers_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('following_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('friends_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('privacy_settings')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
