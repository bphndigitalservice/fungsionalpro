<?php

namespace App\Filament\Pages\Client;


use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use App\Enums\ClientCluster;
use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\RegDepartment;
use App\Models\RegDepartmentEchelon1;
use App\Models\RegProvince;
use App\Models\RegRegency;
use Filament\Forms;
use Illuminate\Contracts\Support\Htmlable;


/**
 * @property \Filament\Schemas\Schema $form
 */
class ClientBasicIdentityPage extends BaseClientProfilePage
{

    public function getTitle(): string|Htmlable
    {
        return __('Informasi Dasar');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('labels.nav.client_menu');
    }

    public static function getNavigationLabel(): string
    {
        return __('Informasi Dasar');
    }

    public static function getRoute(): string
    {
        return '/c/profile/basic-information';
    }

    function initializePage(): void
    {
        $this->fillForm();
        $this->previousUrl = url()->previous();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading(__('labels.form.client.heading.client_identity'))
                    ->description(__('labels.form.client.heading.client_identity_description'))
                    ->collapsible()
                    ->schema([
                        Group::make()
                            ->schema(ClientResource::getClientIdentityForm())
                            ->columnSpan(5),
                    ])->columnSpan(['lg' => fn(?Client $record) => $record === null ? 3 : 2]),
                Section::make()
                    ->heading(__('labels.form.client.heading.client_employee_information'))
                    ->description(__('labels.form.client.heading.client_employee_information_description'))
                    ->collapsible()
                    ->schema([
                        Group::make()
                            ->schema(ClientResource::getClientBasicInformationForm(fn() => static::getRecord()))
                            ->columnSpan(5),
                    ])->columnSpan(['lg' => fn(?Client $record) => $record === null ? 3 : 2]),
            ]);

    }

    function save(array $data): Client
    {
        $record = new Client($data);
        $record->save();

        $this->form->model($this->getRecord())->saveRelationships();

        return $record;
    }


    public function mutateFormDataBeforeSave(array $data): array
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

        if ($data['type'] === ClientCluster::Central->value) {
            $data['echelon_x_text'] = null;
        } else {
            $data['echelon_id'] = null;
        }

        return $data;
    }

    function mutateDataBeforeFill(array $data): array
    {
        if (is_null(static::$record)) {
            $data['name'] = auth('web')->user()->name;
        }

        return $data;
    }
}
