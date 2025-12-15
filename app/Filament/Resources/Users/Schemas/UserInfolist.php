<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('email_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('username')
                    ->placeholder('-'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('bio')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('avatar')
                    ->placeholder('-'),
                TextEntry::make('cover_photo')
                    ->placeholder('-'),
                TextEntry::make('date_of_birth')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('gender')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('location')
                    ->placeholder('-'),
                IconEntry::make('is_verified')
                    ->boolean(),
                IconEntry::make('is_creator')
                    ->boolean(),
                IconEntry::make('is_admin')
                    ->boolean(),
                TextEntry::make('creator_approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('is_banned')
                    ->boolean(),
                TextEntry::make('banned_reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('balance')
                    ->numeric(),
                TextEntry::make('total_earned')
                    ->numeric(),
                TextEntry::make('followers_count')
                    ->numeric(),
                TextEntry::make('following_count')
                    ->numeric(),
                TextEntry::make('friends_count')
                    ->numeric(),
                TextEntry::make('privacy_settings')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
