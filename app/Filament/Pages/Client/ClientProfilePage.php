<?php

namespace App\Filament\Pages\Client;

use App\Concerns\Client\CanUseProfileNote;
use App\Enums\ClientCluster;
use App\Enums\Verified;
use App\Events\ClientProfileUpdated;
use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\RegDepartment;
use App\Models\RegDepartmentEchelon1;
use App\Models\RegProvince;
use App\Models\RegRegency;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Concerns\HasUnsavedDataChangesAlert;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * @property Form $form
 */
class ClientProfilePage extends Page implements HasForms, HasInfolists
{
    use CanUseDatabaseTransactions;
    use CanUseProfileNote;
    use HasPageShield, HasUnsavedDataChangesAlert, InteractsWithFormActions, InteractsWithForms, InteractsWithInfolists;

    protected static string $view = 'filament.pages.client-profile-page';

    public static ?Client $record;

    public ?array $data = [];

    public string $previousUrl;

    public function mount(): void
    {
        $this->getRecord();
        $this->fillForm();
        $this->previousUrl = url()->previous();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Group::make()
                ->schema([
                    Grid::make()
                        ->schema([
                            TextEntry::make(__('Status Verifikasi'))->state(fn () => $this->getVerificationNote()),
                            TextEntry::make('Keterangan')
                                ->state(fn () => $this->getProfileNote()),
                        ])->columns(2),
                ])->columnSpan(2),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema(static::getClientIdentityForm());
    }

    public static function getClientIdentityForm(): array
    {
        return [
            Forms\Components\Tabs::make()
                ->schema([
                    Forms\Components\Tabs\Tab::make(__('labels.form.client.tab_info'))
                        ->schema([
                            Forms\Components\Section::make()
                                ->heading(__('labels.form.client.heading.client_identity'))
                                ->description(__('labels.form.client.heading.client_identity_description'))
                                ->collapsible()
                                ->schema([
                                    Forms\Components\Group::make()
                                        ->schema(ClientResource::getClientIdentityForm())
                                        ->columnSpan(5),
                                ])->columnSpan(['lg' => fn (?Client $record) => $record === null ? 3 : 2]),
                            Forms\Components\Section::make()
                                ->heading(__('labels.form.client.heading.client_education'))
                                ->description(__('labels.form.client.heading.client_education_description'))
                                ->collapsible()
                                ->schema([
                                    Forms\Components\Group::make()
                                        ->schema(ClientResource::getClientEducationForm())
                                        ->columnSpan(5),
                                ])->columnSpan(['lg' => fn (?Client $record) => $record === null ? 3 : 2]),
                            Forms\Components\Section::make()
                                ->heading(__('labels.form.client.heading.client_employee_information'))
                                ->description(__('labels.form.client.heading.client_employee_information_description'))
                                ->collapsible()
                                ->schema([
                                    Forms\Components\Group::make()
                                        ->schema(ClientResource::getClientBasicInformationForm(fn () => static::getRecord()))
                                        ->columnSpan(5),
                                ])->columnSpan(['lg' => fn (?Client $record) => $record === null ? 3 : 2]),

                        ]),
                    Forms\Components\Tabs\Tab::make(__('labels.form.client.tab_file'))
                        ->schema(ClientResource::getDetailedClientForm()),
                ])->columnSpan(5),
        ];
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

    protected function resolveRecord(): array
    {
        if (is_null(static::$record)) {
            return [];
        }

        return [
            ...static::$record->attributesToArray(),
            'identity' => static::$record->identity->attributesToArray() ?? [],
            'education' => static::$record->education->attributesToArray() ?? [],
            'detail' => static::$record->detail->attributesToArray() ?? [],
        ];
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form($this->makeForm()
                ->operation('submit')
                ->model(Client::class)
                ->statePath($this->getFormStatePath())
            ),
        ];
    }

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_id'] = auth('web')->user()->id;

        $data['agency_type'] = match ($data['type']) {
            ClientCluster::Central->value => RegDepartment::class,
            ClientCluster::LocalProvince->value => RegProvince::class,
            ClientCluster::LocalRegency->value => RegRegency::class,
        };

        $data['echelon_type'] = match ($data['type']) {
            ClientCluster::Central->value => RegDepartmentEchelon1::class,
            ClientCluster::LocalProvince->value => RegProvince::class,
            ClientCluster::LocalRegency->value => RegRegency::class,
        };

        $data['echelon_x_text'] = $data['type'] == ClientCluster::Central->value ? null : $data['type'];

        return $data;
    }

    protected function mutateDataBeforeFill(array $data): array
    {
        if (is_null(static::$record)) {
            $data['name'] = auth('web')->user()->name;
        }

        return $data;
    }

    protected function handleSave(array $data): Client
    {
        $this->authorizeAccess();

        if (is_null($this->getRecord())) {
            return $this->save($data);
        }

        return $this->update($data);
    }

    protected function save(array $data): Client
    {
        $record = new Client($data);
        $record->save();

        $this->form->model($this->getRecord())->saveRelationships();

        return $record;
    }

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

    public static function getRecord(): ?Client
    {
        $record = Client::with(['education', 'identity', 'detail'])->where('user_id', auth('web')->user()->id)->first();
        self::$record = $record;

        return static::$record;
    }

    public function saveClientProfileAction(): Action
    {
        return Action::make('submit')
            ->label('Submit')
            ->action(fn () => $this->submit())
            ->keyBindings(['mod+s']);
    }

    protected function authorizeAccess(): void
    {
        abort_unless(auth('web')->user()->can(['create_client', 'update_client']), 403);
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

    public function getBreadcrumbs(): array
    {
        return [
            '/' => 'Dasbor',
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.page.client_profile.nav');
    }

    public function getTitle(): string|Htmlable
    {
        return __('labels.page.client_profile.title');
    }

    public static function canView(): bool
    {
        return Filament::auth()->user()->can(static::getPermissionName());
    }

    public static function getNavigationGroup(): ?string
    {
        return __('labels.nav.client_menu');
    }

    public static function getRoutePath(): string
    {
        return '/c/profile';
    }

    private static function currentClient(): ?Model
    {
        return auth()->user()->client;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
