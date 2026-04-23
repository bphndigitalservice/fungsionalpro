<?php

namespace App\Models;

use App\Concerns\Verifier\InteractWithClientData;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tapp\FilamentInvite\Notifications\SetPassword;


class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, HasPanelShield, HasRoles, Notifiable;
    use InteractWithClientData;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function client(): HasOne
    {
        return $this->hasOne(Client::class, 'user_id', 'id');
    }

    public function isActiveClient(): bool
    {
        return $this->hasRole('client') && !is_null($this->client);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(['super_admin']);
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // Only allow access if user has appropriate roles or is an active client
        return $this->hasRole(['super_admin', 'admin', 'verifier']) || $this->isActiveClient();
    }

    public function getResetPasswordUrl(string $token, array $parameters = []): string
    {
        return URL::signedRoute(
            'filament.admin.auth.password-reset.reset',
            [
                'email' => $this->email,
                'token' => $token,
                ...$parameters,
            ],
        );
    }

    public function sendPasswordSetNotification($token): void
    {
        Notification::send($this, new SetPassword($token));
    }

    public function adminAccesses()
    {
        return $this->hasMany(AdminAccess::class);
    }

}
