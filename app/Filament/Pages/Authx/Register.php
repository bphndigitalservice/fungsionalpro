<?php

namespace App\Filament\Pages\Authx;

use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Events\Auth\Registered;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;
use App\Models\MasterClient;
use App\Models\User;
use App\Models\Client;
use App\Models\CRole;
use Illuminate\Support\Facades\Hash;

class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nama Lengkap')
                ->required(),
            
            TextInput::make('nip')
                ->label('NIP')
                ->required()
                ->unique('clients', 'nip'),

            Select::make('c_role_id')
                ->label('Jabatan Fungsional')
                ->options([
                    1 => 'Analis Hukum',
                    2 => 'Penyuluh Hukum',
                ])
                ->required(),

            Select::make('agency_type')
                ->label('Tingkat Instansi')
                ->options([
                    'central' => 'Pusat',
                    'local_province' => 'Provinsi',
                    'local_regency' => 'Kabupaten/Kota',
                ])
                ->live()
                ->required(),

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
                ->disabled(fn (Get $get) => ! $get('agency_type')),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique('users', 'email'),

            TextInput::make('password')
                ->label('Password')
                ->password()
                ->required(),

            TextInput::make('passwordConfirmation')
                ->label('Konfirmasi Password')
                ->password()
                ->required()
                ->same('password'),
        ]);
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function () {
            $this->callHook('beforeValidate');
            $data = $this->form->getState();
            $this->callHook('afterValidate');
            
            $data = $this->mutateFormDataBeforeRegister($data);
            
            $this->callHook('beforeRegister');
            $user = $this->handleRegistration($data);
            
            $this->form->model($user)->saveRelationships();
            $this->callHook('afterRegister');
            
            return $user;
        });

        event(new Registered($user));
        $this->sendEmailVerificationNotification($user);
        Filament::auth()->login($user);
        session()->regenerate();

        return app(RegistrationResponse::class);
    }

    protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = \App\Models\User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
        ]);

        $user->client()->create([
            'nip' => $data['nip'],
            'c_role_id' => $data['c_role_id'],
            'type' => $data['agency_type'],
            'agency_type' => $this->getAgencyModel($data['agency_type']),
            'agency_id' => $data['agency_id'],
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