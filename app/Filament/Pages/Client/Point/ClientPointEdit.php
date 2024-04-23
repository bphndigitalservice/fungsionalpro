<?php

namespace App\Filament\Pages\Client\Point;

use App\Concerns\Point\SubmissionRule;
use App\Enums\PointSubmissionStatus;
use App\Exceptions\ExceedMaxPointSubmission;
use App\Models\Client;
use App\Models\ClientPointSubmission;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Js;

use function Filament\Support\is_app_url;

/**
 * @property Form $form
 */
class ClientPointEdit extends Page implements HasForms, HasInfolists
{
    use CanUseDatabaseTransactions;
    use HasPageShield, HasUnsavedDataChangesAlert, InteractsWithFormActions, InteractsWithForms, InteractsWithInfolists;

    protected static string $view = 'filament.pages.client-point-edit';

    protected static bool $shouldRegisterNavigation = false;

    public ClientPointSubmission $record;

    public ?array $data = [];

    public string $previousUrl;

    public function mount(ClientPointSubmission $pointSubmission): void
    {
        $this->record = $this->resolveRecord($pointSubmission->id);
        $this->fillForm($this->record->attributesToArray());
        $this->previousUrl = url()->previous();
    }

    public function resolveRecord(string $id): ClientPointSubmission
    {
        $record = ClientPointSubmission::where('id', $id)->first();
        if (is_null($record)) {
            throw new ModelNotFoundException();
        }

        return $record;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('submission_bag_id')
                    ->label(__('Periode'))
                    ->required()
                    ->relationship('bag', 'label'),
                ClientPointCreate::getSubmissionTypeField(),
                ClientPointCreate::getSKP2AKConversionForm(),
                ClientPointCreate::getSKPAccumulation(),
                ClientPointCreate::getFinalAKForm(),
                ClientPointCreate::getSKP2AkFileUploadField(),
                ClientPointCreate::getAccumulatedAKFileUploadField(),
                ClientPointCreate::getFinalPAKUploadField(),
            ]);
    }

    protected function fillForm(array $data): void
    {
        $this->form->fill($data);
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form($this->makeForm())
                ->operation('submit')
                ->model(ClientPointSubmission::class)
                ->statePath($this->getFormStatePath()),
        ];
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    public function getFormActions(): array
    {
        return [
            $this->submitPointAction(),
            $this->cancelFormAction(),
        ];
    }

    public function submitPointAction(): Action
    {
        return Action::make('submit')
            ->label(__('Submit'))
            ->action(fn() => $this->update())
            ->keyBindings(['mod+s']);
    }

    protected function cancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label(__('Cancel'))
            ->alpineClickHandler('document.referrer ? window.history.back() : (window.location.href = ' . Js::from($this->previousUrl) . ')')
            ->color('gray');
    }

    protected function mutateDataBeforeUpdate(array $data): array
    {
        $data['client_id'] = Client::current()->id;
        $data['status'] = PointSubmissionStatus::Submitted;
        $data['revised_at'] = now();

        return $data;
    }

    public function update(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();
            $data = $this->mutateDataBeforeUpdate($data);

            $this->handleRecordUpdate($this->record, $data);

            $this->commitDatabaseTransaction();

        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            Log::error($exception->getMessage(), $data);

            $this->getErrorNotification('error', 'Something went wrong')->send();

            return;
        } catch (\Throwable $exception) {
            $this->rollBackDatabaseTransaction();
            Log::error($exception->getMessage(), $data);

            $this->getErrorNotification('error', 'Something went wrong')->send();

            return;
        }

        $this->getUpdateNotification()?->send();

        $redirectUrl = $this->getRedirectUrl();

        $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode() && is_app_url($redirectUrl));

    }

    protected function handleRecordUpdate(ClientPointSubmission $pointSubmission, array $data): void
    {
        if (SubmissionRule::isExceededMaxSubmission($data['submission_bag_id'], $data['submission_type'], $data['client_id'])) {
            throw new ExceedMaxPointSubmission();
        }

        $pointSubmission->update($data);
    }

    protected function getUpdateNotification(): ?Notification
    {
        $title = $this->getUpdatedNotificationTitle();

        if (blank($title)) {
            return null;
        }

        return Notification::make()
            ->success()
            ->title($title);

    }

    protected function getUpdatedNotificationTitle(): string
    {
        return $this->getUpdateNotificationMessage() ?? __('Perbaikan pelaporan angka kredit terkirim.');
    }

    protected function getUpdateNotificationMessage(): ?string
    {
        return null;
    }

    protected function getErrorNotification(string $title, string $message): Notification
    {
        return Notification::make()
            ->danger()
            ->title($title)
            ->body($message);
    }

    protected function getRedirectUrl(): string
    {
        return ClientPointList::getRoutePath();
    }

    public function getBreadcrumbs(): array
    {
        return [
            '#' => 'Angka Kredit',
            '#' => 'Pelaporan',
            self::getRoutePath() => 'Perbaikan',
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('labels.page.client_point_edit.title');
    }

    public static function getRoutePath(): string
    {
        return '/c/points/revise/{pointSubmission}';
    }
}
