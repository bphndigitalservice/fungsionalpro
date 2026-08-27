<?php

namespace App\Filament\Resources;

use App\Enums\SystemRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Client;
use App\Models\CRole;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Spatie\Permission\Models\Role;
use Tapp\FilamentInvite\Tables\InviteAction;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function rolesIncludeClient(?array $roleIds): bool
    {
        if (blank($roleIds)) {
            return false;
        }

        return Role::query()
            ->where('name', SystemRole::Client->value)
            ->whereIn('id', $roleIds)
            ->exists();
    }

    public static function syncClientForUser(User $user, array $clientData): void
    {
        $user->loadMissing('roles');

        if (! $user->hasSystemRole(SystemRole::Client)) {
            return;
        }

        Client::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nip' => $clientData['nip'] ?? null,
                'c_role_id' => $clientData['c_role_id'] ?? null,
            ]
        );
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('labels.form.user.heading.role'))
                    ->description(__('labels.form.user.heading.role_description'))
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label(__('labels.form.user.fields.role'))
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload(true)
                            ->live()
                            ->required(),
                    ]),
                Forms\Components\Section::make(__('Client'))
                    ->visible(fn (Get $get): bool => static::rolesIncludeClient($get('roles')))
                    ->schema([
                        Forms\Components\TextInput::make('nip')
                            ->label('NIP')
                            ->required(fn (Get $get): bool => static::rolesIncludeClient($get('roles')))
                            ->maxLength(255)
                            ->unique(
                                table: Client::class,
                                column: 'nip',
                                ignorable: fn (?User $record): ?Client => $record?->client,
                                modifyRuleUsing: fn (Unique $rule) => $rule,
                            ),
                        Forms\Components\Select::make('c_role_id')
                            ->label('Jabatan Fungsional')
                            ->options(fn () => CRole::query()->orderBy('role_name')->pluck('role_name', 'id'))
                            ->searchable()
                            ->required(fn (Get $get): bool => static::rolesIncludeClient($get('roles'))),
                    ]),
                Forms\Components\Section::make(__('labels.form.user.heading.general'))
                    ->collapsible()
                    ->description(__('labels.form.user.heading.general_description'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('labels.form.user.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->label(__('labels.form.user.fields.password'))
                            ->password()
                            ->revealable()
                            ->required(fn (string $context): bool => $context == 'create')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->autocomplete(false)
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('generatePassword')
                                    ->icon('heroicon-m-key')
                                    ->tooltip(__('Generate password'))
                                    ->action(function (Set $set): void {
                                        $password = Str::password(12, letters: true, numbers: true, symbols: false);

                                        $set('password', $password);

                                        Notification::make()
                                            ->title(__('Password generated'))
                                            ->body($password)
                                            ->success()
                                            ->persistent()
                                            ->send();
                                    })
                            ),
                    ]),
                Forms\Components\Section::make(__('labels.form.user.heading.verification'))
                    ->description(__('labels.form.user.heading.verification_descritpion'))
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
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
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
