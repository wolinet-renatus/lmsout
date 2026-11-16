<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * BranchScoped Trait
 *
 * Applies automatic regional-center (branch_id) query isolation for branch-level
 * staff roles while preserving unrestricted multi-branch access for global executives,
 * and auto-assigns the creator's branch_id when persisting new scoped records.
 *
 * @package App\Traits
 * @author Renatus Bernardo
 */
trait BranchScoped
{
    /**
     * Roles that have unrestricted cross-branch visibility.
     *
     * @return array<int, string>
     */
    public static function crossBranchRoles(): array
    {
        return [
            'super-admin',
            'admin',
            'director',
            'deputy-director',
            'marketing-officer',
            'system-administrator',
        ];
    }

    /**
     * Boot the branch scope for campus-scoped models.
     */
    protected static function bootBranchScoped(): void
    {
        static::addGlobalScope('branch', function (Builder $builder): void {
            if ((app()->runningInConsole() && ! app()->runningUnitTests()) || ! Auth::check()) {
                return;
            }

            $user = Auth::user();
            if (! $user) {
                return;
            }

            // Students and Instructors are already scoped by their own user_id / course_id in portals
            if (in_array($user->role, ['student', 'instructor'], true) && ! $user->hasAnyRole(['branch-registrar', 'coordinator', 'accountant', 'staff'])) {
                return;
            }

            if ($user->hasAnyRole(static::crossBranchRoles()) || $user->can('manage-all')) {
                return;
            }

            if (! $user->branch_id) {
                return;
            }

            $model = $builder->getModel();

            if (method_exists($model, 'applyBranchScope')) {
                $model->applyBranchScope($builder, (int) $user->branch_id);
                return;
            }

            if (in_array('branch_id', $model->getFillable(), true)) {
                $builder->where($model->getTable() . '.branch_id', (int) $user->branch_id);
            }
        });

        static::creating(function (Model $model): void {
            if (! Auth::check()) {
                return;
            }

            $user = Auth::user();
            if ($user && $user->branch_id && in_array('branch_id', $model->getFillable(), true) && empty($model->branch_id)) {
                if (! $user->hasAnyRole(static::crossBranchRoles())) {
                    $model->branch_id = $user->branch_id;
                }
            }
        });
    }

    /**
     * Query scope to explicitly bypass branch filtering when needed.
     */
    public function scopeWithoutBranchScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('branch');
    }
}
