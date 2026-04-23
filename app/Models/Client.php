<?php

namespace App\Models;

use App\Concerns\Client\ProfileMustComplete;
use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\CRoleAssignation;
use App\Enums\Verified;
use App\Events\ClientProfileCompleted;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\Notifiable;

class Client extends Model
{
    use Notifiable;
    use HasFactory, HasUlids;
    use ProfileMustComplete;

    protected $casts = [
        'type' => ClientCluster::class,
        'status' => ClientStatus::class,
        'assignation_type' => CRoleAssignation::class,
        'is_verified' => Verified::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(RegGrade::class, 'reg_grade_id', 'id')->orderBy('id', 'asc');
    }

    public function crole(): BelongsTo
    {
        return $this->belongsTo(CRole::class, 'c_role_id', 'id');
    }

    public function croleLevel(): BelongsTo
    {
        return $this->belongsTo(CRoleLevel::class, 'c_role_level_id', 'id')->orderBy('id', 'asc');
    }

    public function detail(): HasOne
    {
        return $this->hasOne(ClientDetail::class);
    }

    public function education(): HasOne
    {
        return $this->hasOne(ClientEducation::class)->orderBy('id', 'desc');
    }

    public function activity(): HasOne
    {
        return $this->hasOne(ClientActivity::class)->orderBy('id', 'desc');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClientActivity::class)->orderBy('id', 'ASC');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(ClientEducation::class)->orderBy('id', 'ASC');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(ClientGrade::class)->orderBy('id', 'DESC');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(ClientPosition::class)->orderBy('id', 'DESC');
    }

    public function dossier(): HasMany
    {
        return $this->hasMany(ClientDossier::class)->orderBy('id', 'DESC');
    }

    public function identity(): HasOne
    {
        return $this->hasOne(ClientIdentity::class);
    }

    public function agenciable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'agency_type', 'agency_id');
    }

    public function echelonable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'echelon_type', 'echelon_id');
    }

    public function point(): HasOne
    {
        return $this->hasOne(ClientPoint::class);
    }

    public function verified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'is_verified' => false,
        ]);
    }

    public static function current(): ?Client
    {
        $user = auth()->user();
        if (is_null($user)) {
            return null;
        }

        return Client::where('user_id', $user->id)->first() ?? null;

    }

    public function note(): HasOne
    {
        return $this->hasOne(VClientNote::class, 'client_id', 'id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Client $model) {
            event(new ClientProfileCompleted($model));
        });

    }

    public function competences(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClientCompetence::class);
    }

    public function hasLatestEducation(): bool
    {
        return $this->latestEducation()->exists();
    }



}
