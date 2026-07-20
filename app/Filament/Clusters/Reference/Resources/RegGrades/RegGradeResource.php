<?php

namespace App\Filament\Clusters\Reference\Resources\RegGrades;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\Reference\Resources\RegGrades\Pages\ListRegGrades;
use App\Filament\Clusters\Reference\Resources\RegGrades\Pages\CreateRegGrade;
use App\Filament\Clusters\Reference\Resources\RegGrades\Pages\ViewRegGrade;
use App\Filament\Clusters\Reference\Resources\RegGrades\Pages\EditRegGrade;
use App\Filament\Clusters\Reference\ReferenceCluster;
use App\Filament\Clusters\Reference\Resources\RegGrades\Pages;
use App\Models\RegGrade;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RegGradeResource extends Resource
{
    protected static ?string $model = RegGrade::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = ReferenceCluster::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Group::make()
                            ->schema([
                                TextInput::make('grade_name')
                                    ->label(__('labels.form.grade.fields.grade_name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('grade_code')
                                    ->label(__('labels.form.grade.fields.grade_code'))
                                    ->required()
                                    ->maxLength(255),
                            ])->columns(2),
                    ])->columnSpan(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grade_name')
                    ->searchable(),
                TextColumn::make('grade_code')
                    ->searchable(),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegGrades::route('/'),
            'create' => CreateRegGrade::route('/create'),
            'view' => ViewRegGrade::route('/{record}'),
            'edit' => EditRegGrade::route('/{record}/edit'),
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
