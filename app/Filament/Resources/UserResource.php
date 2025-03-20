<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Tapp\FilamentInvite\Tables\InviteAction;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('labels.form.user.heading.general'))
                    ->collapsible()
                    ->collapsed()
                    ->description(__('labels.form.user.heading.general_description'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('labels.form.user.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label(__('labels.form.user.fields.password'))
                            ->password()
                            ->required(fn (string $context): bool => $context == 'create')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->autocomplete(false),
                    ]),
                Forms\Components\Section::make(__('labels.form.user.heading.role'))
                    ->description(__('labels.form.user.heading.role_description'))
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label(__('labels.form.user.fields.role'))
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload(true),
                    ]),
                Forms\Components\Section::make(__('labels.form.user.heading.verification'))
                    ->description(__('labels.form.user.heading.verification_description'))
                    ->collapsible()
                    ->schema([
                        Forms\Components\DateTimePicker::make('email_verified_at')->label(__('labels.form.user.fields.verification')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                InviteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.system');
    }
}
