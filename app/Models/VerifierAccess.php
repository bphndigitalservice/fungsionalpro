<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\JoinClause;

class VerifierAccess extends Model
{
    use HasFactory;

    protected $guarded = ['id'];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(CRole::class, 'c_role_id', 'id');
    }

    public function accessible(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }

    public function isBphnGlobalScope(): bool
    {
        return $this->entity_id === null;
    }

    /**
     * @param  Builder<Model>|\Illuminate\Database\Query\Builder  $query
     */
    public static function constrainMatchingClientAgency(Builder|\Illuminate\Database\Query\Builder $query, string $alias = 'va'): void
    {
        $query->where(function ($scope) use ($alias): void {
            $scope->whereNull("{$alias}.entity_id")
                ->orWhere(function ($entity) use ($alias): void {
                    $entity->whereColumn("{$alias}.entity_type", 'clients.agency_type')
                        ->whereColumn("{$alias}.entity_id", 'clients.agency_id');
                });
        });
    }

    public static function joinClientAgencyMatch(JoinClause $join): void
    {
        $join->on('clients.c_role_id', '=', 'va.c_role_id')
            ->where(function ($query): void {
                $query->whereNull('va.entity_id')
                    ->orWhere(function ($entity): void {
                        $entity->whereColumn('va.entity_type', 'clients.agency_type')
                            ->whereColumn('va.entity_id', 'clients.agency_id');
                    });
            });
    }
}
