<?php

namespace App\Filament\Pages\Authx;

use App\Models\Client;
use App\Models\CRole;
use App\Models\MasterJf;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        TextInput::make('nip')
                            ->label('NIP')
                            ->required()
                            ->unique('clients', 'nip')
                            ->suffixAction(
                                FormAction::make('Cari')
                                    ->button()
                                    ->color('primary')
                                    ->icon('heroicon-m-magnifying-glass')
                                    ->action(function (Get $get, Set $set) {
                                        $nip = $get('nip');
                                        if (!$nip) return;

                                        $set('name', null);
                                        $set('c_role_id', null);
                                        $set('agency_type', null);
                                        $set('agency_id', null);
                                        $set('email', null);
                                        $set('password', null);
                                        $set('passwordConfirmation', null);

                                        $set('search_message', null);
                                        $set('search_message_type', null);
                                        $set('client_found', false);

                                        $client = Client::where('nip', $nip)->with('user')->first();
                                        if ($client) {
                                            $set('email', $client->user->email);
                                            $set('search_message', 'Anda sudah memiliki akun. Silahkan login');
                                            $set('search_message_type', 'danger');
                                            $set('client_found', true);
                                            return;
                                        }

                                        $masterJf = MasterJf::where('nip', $nip)->first();
                                        if ($masterJf) {
                                            $set('name', trim(explode(',', $masterJf->nama ?? '')[0]));

                                            if (str_contains(strtolower($masterJf->jabatan), 'analis')) {
                                                $set('c_role_id', 1);
                                            } elseif (str_contains(strtolower($masterJf->jabatan), 'penyuluh')) {
                                                $set('c_role_id', 2);
                                            }

                                            [$type, $model] = \App\Services\ClientMatchingService::determineAgencyInfo($masterJf->instansi ?? '', $masterJf->unit_kerja ?? '');
                                            $set('agency_type', $type);

                                            // Need to lookup agency_id
                                            $cleanUnitKerja = \App\Services\ClientMatchingService::cleanAgencyName($masterJf->unit_kerja);
                                            $cleanInstansi = \App\Services\ClientMatchingService::cleanAgencyName($masterJf->instansi);

                                            $agency = $model::where('name', '=', $cleanUnitKerja)->first();
                                            if (!$agency && $cleanInstansi) {
                                                $agency = $model::where('name', '=', $cleanInstansi)->first();
                                            }
                                            if (!$agency && $cleanUnitKerja) {
                                                $agency = $model::where('name', 'LIKE', "%" . $cleanUnitKerja . "%")->first();
                                            }
                                            if (!$agency && $cleanInstansi) {
                                                $agency = $model::where('name', 'LIKE', "%" . $cleanInstansi . "%")->first();
                                            }

                                            if ($agency) {
                                                $set('agency_id', $agency->id);
                                            }

                                            $set('search_message', 'Data ditemukan. Lanjutkan pendaftaran.');
                                            $set('search_message_type', 'success');
                                        } else {
                                            $set('search_message', 'Data tidak ditemukan. Lanjutkan pendaftaran dengan data yang sesuai.');
                                            $set('search_message_type', 'warning');
                                        }
                                    })
                            ),

                        Hidden::make('search_message'),
                        Hidden::make('search_message_type'),
                        Hidden::make('client_found')->default(false),
                        Placeholder::make('search_message_display')
                            ->label(false)
                            ->content(fn(Get $get) => new \Illuminate\Support\HtmlString(
                                '<span style="' . match($get('search_message_type')) {
                                    'danger' => 'color: #dc2626; font-weight: 500;',  // Red
                                    'success' => 'color: #16a34a; font-weight: 500;', // Green
                                    'warning' => 'color: #ea580c; font-weight: 500;', // Amber / Dark Orange
                                    default => 'color: #4b5563;',                    // Gray
                                } . '">' . e($get('search_message')) . '</span>'
                            ))
                            ->visible(fn(Get $get) => $get('search_message') !== null),

                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->disabled(fn (Get $get) => $get('client_found')),

                        Select::make('c_role_id')
                            ->label('Jabatan Fungsional')
                            ->options([
                                1 => 'Analis Hukum',
                                2 => 'Penyuluh Hukum',
                            ])
                            ->required()
                            ->disabled(fn (Get $get) => $get('c_role_id') !== null || $get('client_found'))
                            ->dehydrated(),

                        Select::make('agency_type')
                            ->label('Tingkat Instansi')
                            ->options([
                                'central' => 'Pusat',
                                'local_province' => 'Provinsi',
                                'local_regency' => 'Kab/Kota',
                            ])
                            ->live()
                            ->required()
                            ->disabled(fn (Get $get) => $get('client_found')),

                        Select::make('agency_id')
                            ->label('Instansi')
                            ->placeholder(fn (Get $get) => $get('agency_type') ? 'Pilih Instansi' : 'Pilih Tingkat Instansi Terlebih Dahulu')
                            ->searchable()
                            ->required()
                            ->options(function (Get $get) {
                                $type = $get('agency_type');

                                return match ($type) {
                                    'central' => RegDepartment::query()->pluck('name', 'id'),
                                    'local_province' => RegProvince::query()->pluck('name', 'id'),
                                    'local_regency' => RegRegency::query()->pluck('name', 'id'),
                                    default => [],
                                };
                            })
                            ->disabled(fn (Get $get) => ! $get('agency_type') || $get('client_found')),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique('users', 'email')
                            ->disabled(fn (Get $get) => $get('client_found')),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required()
                            ->disabled(fn (Get $get) => $get('client_found')),

                        TextInput::make('passwordConfirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->required()
                            ->disabled(fn (Get $get) => $get('client_found')),

                    ])
                    ->statePath('data'),
            ),
        ];
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->callHook('beforeValidate');
            $data = $this->form->getState();
            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');
            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();
            $this->callHook('afterRegister');

            // Logs the user into the panel environment dynamically
            Filament::auth()->login($user);
            session()->regenerate();

            return app(RegistrationResponse::class);
        } catch (\Exception $exception) {
            Notification::make()
                ->title('Pendaftaran gagal')
                ->body($exception->getMessage())
                ->danger()
                ->send();
            return null;
        }
    }

    protected function handleRegistration(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        // Using your SystemRole enum dynamically to assign the 'client' role
        $user->assignRole(\App\Enums\SystemRole::Client->value);

        Client::create([
            'user_id' => $user->id,
            //'name' => $data['name'],
            'nip' => $data['nip'],
            'c_role_id' => $data['c_role_id'],
            'agency_id' => $data['agency_id'],
            'type' => $data['agency_type'],
            'agency_type' => $this->getAgencyModel($data['agency_type']),
        ]);

        return $user;
    }

    protected function getAgencyModel(string $type): string
    {
        return match ($type) {
            'central' => RegDepartment::class,
            'local_province' => RegProvince::class,
            'local_regency' => RegRegency::class,
            default => RegDepartment::class,
        };
    }
}
