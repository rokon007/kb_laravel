<?php

namespace App\Filament\Resources\Advertisements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdvertisementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('media_type')
                    ->badge(),
                TextColumn::make('media_url')
                    ->searchable(),
                TextColumn::make('click_url')
                    ->searchable(),
                TextColumn::make('placement_type')
                    ->badge(),
                TextColumn::make('target_age_min')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('target_age_max')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('target_gender')
                    ->badge(),
                TextColumn::make('budget')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('spent')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('impressions')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('clicks')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
