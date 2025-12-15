<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('page_id')
                    ->numeric()
                    ->default(null),
                Textarea::make('content')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('type')
                    ->options(['text' => 'Text', 'image' => 'Image', 'video' => 'Video', 'reel' => 'Reel'])
                    ->required(),
                TextInput::make('media_url')
                    ->url()
                    ->default(null),
                TextInput::make('thumbnail_url')
                    ->url()
                    ->default(null),
                TextInput::make('video_duration')
                    ->numeric()
                    ->default(null),
                Select::make('privacy')
                    ->options(['public' => 'Public', 'friends' => 'Friends', 'private' => 'Private'])
                    ->default('public')
                    ->required(),
                Toggle::make('is_boosted')
                    ->required(),
                TextInput::make('boost_budget')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('likes_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('comments_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('shares_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('views_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->options(['active' => 'Active', 'reported' => 'Reported', 'removed' => 'Removed'])
                    ->default('active')
                    ->required(),
            ]);
    }
}
