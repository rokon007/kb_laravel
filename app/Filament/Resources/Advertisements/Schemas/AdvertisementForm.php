<?php

namespace App\Filament\Resources\Advertisements\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AdvertisementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('media_type')
                    ->options(['image' => 'Image', 'video' => 'Video'])
                    ->required(),
                TextInput::make('media_url')
                    ->url()
                    ->required(),
                TextInput::make('click_url')
                    ->url()
                    ->required(),
                Select::make('placement_type')
                    ->options([
            'feed' => 'Feed',
            'reel' => 'Reel',
            'video_preroll' => 'Video preroll',
            'video_midroll' => 'Video midroll',
            'sponsored' => 'Sponsored',
        ])
                    ->required(),
                TextInput::make('target_age_min')
                    ->numeric()
                    ->default(null),
                TextInput::make('target_age_max')
                    ->numeric()
                    ->default(null),
                Select::make('target_gender')
                    ->options(['all' => 'All', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'])
                    ->default('all')
                    ->required(),
                Textarea::make('target_locations')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('target_interests')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('budget')
                    ->required()
                    ->numeric(),
                TextInput::make('spent')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('impressions')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('clicks')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'running' => 'Running',
            'paused' => 'Paused',
            'completed' => 'Completed',
        ])
                    ->default('pending')
                    ->required(),
                Textarea::make('admin_note')
                    ->default(null)
                    ->columnSpanFull(),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
            ]);
    }
}
