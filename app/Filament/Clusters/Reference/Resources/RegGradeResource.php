<?php

namespace App\Filament\Clusters\Reference\Resources;

use App\Filament\Clusters\Reference;
use App\Filament\Clusters\Reference\Resources\RegGradeResource\Pages;
use App\Filament\Clusters\Reference\Resources\RegGradeResource\RelationManagers;
use App\Models\RegGrade;
use App\Models\RegProvince;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PHPUnit\Metadata\Group;

class RegGradeResource extends Resource
{
    protected static ?string $model = RegGrade::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Reference::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('grade_name')
                                    ->label(__('labels.form.grade.fields.grade_name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('grade_code')
                                    ->label(__('labels.form.grade.fields.grade_code'))
                                    ->required()
                                    ->maxLength(255),
                            ])->columns(2)
                    ])->columnSpan(5)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('grade_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('grade_code')
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
            'index' => Pages\ListRegGrades::route('/'),
            'create' => Pages\CreateRegGrade::route('/create'),
            'view' => Pages\ViewRegGrade::route('/{record}'),
            'edit' => Pages\EditRegGrade::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('labels.nav.references_grade');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.nav.references_grade');
    }

    public static function getNavigationBadge(): ?string
    {
        return RegGrade::query()->count();
    }
}
