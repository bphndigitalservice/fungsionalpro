<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VerifierAccessResource\Pages;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Models\User;
use App\Models\VerifierAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class VerifierAccessResource extends Resource
{
    protected static ?string $model = VerifierAccess::class;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole(['super_admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->hasRole(['super_admin']) ?? false;
    }

    public static function canEdit(): bool
    {
        return Auth::user()?->hasRole(['super_admin']) ?? false;
    }

    public static function canDelete(): bool
    {
        return Auth::user()?->hasRole(['super_admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('c_role_id')
                                    ->searchable()
                                    ->relationship('role', 'role_name')
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('user_id')
                                    ->searchable()
                                    ->relationship('user', 'name', modifyQueryUsing: fn () => User::role(['verifier', 'admin-regional']))
                                    ->preload()
                                    ->required(),
                            ])->columns(2),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\MorphToSelect::make('accessible')
                                    ->types([
                                        Forms\Components\MorphToSelect\Type::make(RegDepartment::class)
                                            ->titleAttribute('name'),
                                        Forms\Components\MorphToSelect\Type::make(RegProvince::class)
                                            ->titleAttribute('name'),
                                        Forms\Components\MorphToSelect\Type::make(RegRegency::class)
                                            ->titleAttribute('name'),
                                    ]),
                            ])->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role.role_name')
                    ->badge()
                    ->label(__('Ruang Jabatan')),
                Tables\Columns\TextColumn::make('accessible.name')
                    ->label(__('Ruang Regional'))
                    ->searchable(),
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
            'index' => Pages\ListVerifierAccesses::route('/'),
            'create' => Pages\CreateVerifierAccess::route('/create'),
            'view' => Pages\ViewVerifierAccess::route('/{record}'),
            'edit' => Pages\EditVerifierAccess::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.nav.regional_access');
    }
}