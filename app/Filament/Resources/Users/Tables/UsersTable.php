<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('avatar')
                    ->searchable(),
                TextColumn::make('cover_photo')
                    ->searchable(),
                TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable(),
                TextColumn::make('gender')
                    ->badge(),
                TextColumn::make('location')
                    ->searchable(),
                IconColumn::make('is_verified')
                    ->boolean(),
                IconColumn::make('is_creator')
                    ->boolean(),
                IconColumn::make('is_admin')
                    ->boolean(),
                TextColumn::make('creator_approved_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_banned')
                    ->boolean(),
                TextColumn::make('balance')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_earned')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('followers_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('following_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('friends_count')
                    ->numeric()
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
