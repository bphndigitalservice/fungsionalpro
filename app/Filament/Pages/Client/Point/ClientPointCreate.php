<?php

namespace App\Filament\Pages\Client\Point;

use App\Concerns\Point\SubmissionRule;
use App\Enums\PointSubmissionStatus;
use App\Enums\PointSubmissionType;
use App\Exceptions\ExceedMaxPointSubmission;
use App\Models\ClientPointSubmission;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Concerns\HasUnsavedDataChangesAlert;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Js;

use function Filament\Support\is_app_url;

/**
 * @property Form $form
 */
class ClientPointCreate extends Page implements HasForms, HasInfolists
{
    use CanUseDatabaseTransactions;
    use HasPageShield, HasUnsavedDataChangesAlert, InteractsWithFormActions, InteractsWithForms, InteractsWithInfolists;

    protected static string $view = 'filament.pages.client-client-point-create';

    protected ClientPointSubmission $record;

    public ?array $data = [];

    public string $previousUrl;

    public function mount(): void
    {
        static::canView();
        $this->fillForm();
        $this->previousUrl = url()->previous();
    }

    public function form(Form $form): Form
    {
        return $form
            ->disabled(! SubmissionRule::hasSubmissionActive())
            ->schema([
                Select::make('submission_bag_id')
                    ->label(__('Periode'))
                    ->required()
                    ->relationship('bag', 'label'),
                static::getSubmissionTypeField(),
                static::getSKP2AKConversionForm(),
                static::getSKPAccumulation(),
                static::getFinalAKForm(),
                static::getSKP2AkFileUploadField(),
                static::getAccumulatedAKFileUploadField(),
                static::getFinalPAKUploadField(),

            ]);
    }

    protected function fillForm(): void
    {
        $this->form->fill();
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form($this->makeForm()
                ->operation('submit')
                ->model(ClientPointSubmission::class)
                ->statePath($this->getFormStatePath())
            ),
        ];
    }

    public function getFormActions(): array
    {
        return [
            $this->submitPointAction(),
            $this->getSubmitAnotherPointFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function submitPointAction(): Action
    {
        return Action::make('submit')
            ->label(__('Submit'))
            ->action(fn () => $this->submit())
            ->keyBindings(['mod+s']);
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label(__('cancel'))
            ->alpineClickHandler('document.referrer ? window.history.back() : (window.location.href = '.Js::from($this->previousUrl).')')
            ->color('gray');
    }

    protected function getSubmitAnotherPointFormAction(): Action
    {
        return Action::make('createAnother')
            ->label(__('Submit & Submit lainnya'))
            ->action('createAnother')
            ->keyBindings(['mod+shift+s'])
            ->color('gray');
    }

    protected function mutateDataBeforeSubmit(array $data): array
    {
        $data = $this->injectCurrentUser($data);
        $data = $this->setInitialSubmissionStatus($data);

        return $data;
    }

    protected function injectCurrentUser(array $data): array
    {
        $data['client_id'] = auth()->user()->client->id;

        return $data;
    }

    protected function setInitialSubmissionStatus(array $data): array
    {
        $data['status'] = PointSubmissionStatus::Submitted;

        return $data;
    }

    public function submit(): void
    {
        try {
            $this->beginDatabaseTransaction();

            // do your things
            $data = $this->form->getState();
            $data = $this->mutateDataBeforeSubmit($data);
            $this->record = $this->handleRecordCreation($data);

            $this->commitDatabaseTransaction();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            Log::error($exception->getMessage(), $data);

            $this->getErrorNotification('error', $exception->getMessage())->send();

            return;
        } catch (\Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            Log::error($exception->getMessage(), $data);

            $this->getErrorNotification('error', $exception->getMessage())->send();

            return;
        }

        $this->getSubmittedNotification()?->send();

        $redirectUrl = $this->getRedirectUrl();

        $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode() && is_app_url($redirectUrl));

    }

    /**
     * @throws ExceedMaxPointSubmission
     */
    public function handleRecordCreation($data): ClientPointSubmission
    {

        if (SubmissionRule::isExceededMaxSubmission($data['submission_bag_id'], $data['submission_type'], $data['client_id'])) {
            throw new ExceedMaxPointSubmission();
        }

        $record = new ClientPointSubmission($data);
        $record->save();

        return $record;
    }

    protected function getSubmittedNotification(): ?Notification
    {
        $title = $this->getSubmittedNotificationTitle();

        if (blank($title)) {
            return null;
        }

        return Notification::make()
            ->success()
            ->title($title);
    }

    protected function getSubmittedNotificationTitle(): string
    {
        return $this->getSubmittedNotificationMessage() ?? __('Pengajuan Angka Kredit Terkirim');
    }

    protected function getSubmittedNotificationMessage(): ?string
    {
        return null;
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    protected function getRedirectUrl(): string
    {
        return ClientPointList::getRoutePath();
    }

    public function getBreadcrumbs(): array
    {
        return [
            '#' => 'Angka Kredit',
            self::getRoutePath() => 'Pengajuan',
        ];
    }

    /*public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make(__('Info'))
                    ->schema([
                        TextEntry::make('Batas Akhir')
                            ->badge()
                            ->state('Besok'),
                    ]),
            ]);
    }*/

    public static function getSKP2AKConversionForm(): Field|Component
    {
        return
            Fieldset::make()
                ->label('Konversi Kinerja (SKP) ke Angka Kredit')
                ->schema([
                    TextInput::make('x_skp2pak_number')
                        ->label(__('Nomor Konversi Predikat Kinerja'))
                        ->required(fn (Get $get) => static::isSKP($get)),
                    TextInput::make('x_skp2pak_point')
                        ->label(__('Nilai Angka Kredit Hasil Konversi'))
                        ->numeric()
                        ->required(fn (Get $get) => static::isSKP($get)),
                ])->hidden(fn (Get $get) => ! static::isSKP($get));
    }

    public static function getSKPAccumulation(): Field|Component
    {
        return Fieldset::make()
            ->label('Akumulasi SKP')
            ->schema([
                TextInput::make('x_accumulated_number')
                    ->label(__('Nomor Akumulasi Angka Kredit'))
                    ->required(fn (Get $get) => static::isSKP($get)),
                TextInput::make('x_accumulated_point')
                    ->label(__('Jumlah Angka Kredit Yang Diperoleh '))
                    ->numeric()
                    ->required(fn (Get $get) => static::isSKP($get)),
            ])->hidden(fn (Get $get) => ! static::isSKP($get));
    }

    public static function getFinalAKForm(): Field|Component
    {
        return Fieldset::make()
            ->label('PAK')
            ->schema([
                TextInput::make('pak_number')
                    ->label(__('Nomor Penetapan Angka Kredit'))
                    ->required(),
                TextInput::make('point')
                    ->label(__('Jumlah Angka Kredit Kumulatif'))
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function getSubmissionTypeField(): Field|Component
    {
        return ToggleButtons::make('submission_type')
            ->label(__('Mekanisme Penilaian'))
            ->options(PointSubmissionType::class)
            ->inline()
            ->live()
            ->required();
    }

    public static function getFinalPAKUploadField(): FileUpload|Component
    {
        return FileUpload::make('pak_file')
            ->downloadable()
            ->directory(config('fungsional-pro.s3.directory.pak_files'))
            ->visibility(config('fungsional-pro.s3.visibility'))
            ->required();
    }

    public static function getSKP2AkFileUploadField(): FileUpload|Component
    {
        return FileUpload::make('x_skp2ak_file')
            ->downloadable()
            ->directory(config('fungsional-pro.s3.directory.pak_files'))
            ->visibility(config('fungsional-pro.s3.visibility'))
            ->hidden(fn (Get $get) => ! static::isSKP($get))
            ->required(fn (Get $get) => static::isSKP($get));
    }

    public static function getAccumulatedAKFileUploadField(): FileUpload|Component
    {
        return FileUpload::make('x_accumulated_file')
            ->downloadable()
            ->directory(config('fungsional-pro.s3.directory.pak_files'))
            ->visibility(config('fungsional-pro.s3.visibility'))
            ->hidden(fn (Get $get) => ! static::isSKP($get))
            ->required(fn (Get $get) => static::isSKP($get));
    }

    protected function getErrorNotification(string $title, string $message): Notification
    {
        return Notification::make()
            ->danger()
            ->title($title)
            ->body($message);
    }

    private static function isSKP(Get $get): bool
    {
        return $get('submission_type') == PointSubmissionType::SKP->value;
    }

    public static function canView(): bool
    {
        return Filament::auth()->user()->can(static::getPermissionName()) || auth()->user()->isActiveClient();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('labels.nav.client_point');
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.page.client_point_create.nav');
    }

    public function getTitle(): string|Htmlable
    {
        return __('labels.page.client_point_create.title');
    }

    public static function getRoutePath(): string
    {
        return '/c/points/create';
    }
}
