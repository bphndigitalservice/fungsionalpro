<?php

namespace App\Filament\Pages\Client;

use App\Events\ClientProfileUpdated;
use App\Models\Client;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Concerns\HasUnsavedDataChangesAlert;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

abstract class BaseClientProfilePage extends Page implements HasForms, HasInfolists
{
    use CanUseDatabaseTransactions;
    use HasPageShield;
    use HasUnsavedDataChangesAlert;
    use InteractsWithFormActions;
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected string $view = 'filament.pages.client-profile-page';

    public static ?Client $record;

    public ?array $data = [];

    public string $previousUrl;

    public function mount(): void
    {
        $this->getRecord();
        $this->initializePage();
    }

    abstract public function initializePage(): void;

    public static function getRecord(): ?Client
    {
        $record = Client::with(['education', 'identity', 'detail'])->where('user_id', auth('web')->user()->id)->first();
        self::$record = $record;

        return static::$record;
    }

    protected function resolveRecord(): array
    {
        if (is_null(static::$record)) {
            return [];
        }

        return [
            ...static::$record->attributesToArray(),
            'identity' => static::$record->identity->attributesToArray() ?? [],
        ];
    }

    public function authorizeAccess(): void
    {
        abort_unless(auth('web')->user()->can(['create_client', 'update_client']), 403);
    }

    protected function fillForm(): void
    {
        $this->fillFormWithData();
    }

    protected function fillFormWithData(): void
    {
        $data = $this->resolveRecord();
        $data = $this->mutateDataBeforeFill($data);
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->getClientProfileFormSchema())
            ->model(Client::class)
            ->statePath($this->getFormStatePath())
            ->operation('submit');
    }

    abstract protected function getClientProfileFormSchema(): array;

    public function getFormActions(): array
    {
        return [
            $this->saveClientProfileAction(),
        ];
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        try {

            $this->beginDatabaseTransaction();

            $data = $this->form->getState();
            $data = $this->mutateFormDataBeforeSave($data);
            static::$record = $this->handleSave($data);

            $this->commitDatabaseTransaction();

        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->rememberData();
        $this->getClientProfileSavedNotification()?->send();
    }

    abstract public function mutateFormDataBeforeSave(array $data): array;

    abstract public function mutateDataBeforeFill(array $data): array;

    protected function handleSave(array $data): Client
    {
        $this->authorizeAccess();

        if (is_null($this->getRecord())) {
            return $this->save($data);
        }

        return $this->update($data);
    }

    abstract public function save(array $data): Client;

    protected function update(array $data): Client
    {
        static::$record->update($data);

        $this->form->model(static::$record)->saveRelationships();

        event(new ClientProfileUpdated(static::$record));

        return static::$record;
    }

    public function getClientProfileSavedNotification(): ?Notification
    {
        $title = $this->getClientProfileSavedNotificationTitle();
        if (blank($title)) {
            return null;
        }

        return Notification::make()
            ->success()
            ->title($title);
    }

    public function saveClientProfileAction(): Action
    {
        return Action::make('submit')
            ->label('Submit')
            ->action(fn () => $this->submit())
            ->keyBindings(['mod+s']);
    }

    protected function getClientProfileSavedNotificationTitle(): string
    {
        return $this->getClientProfileSavedNotificationMessage() ?? 'Profil telah disimpan';
    }

    protected function getClientProfileSavedNotificationMessage(): ?string
    {
        return null;
    }

    public function getFormStatePath(): ?string
    {
        return 'data';
    }

    public static function currentClient(): ?Model
    {
        return auth()->user()->client;
    }
}
