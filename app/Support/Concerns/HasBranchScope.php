<?php

namespace App\Support\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasBranchScope
{
    protected static function applyBranchScope(Builder $query): Builder
    {
        $user = auth()->user();

        if (!$user) {
            return $query;
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if (!$user->branch_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('branch_id', $user->branch_id);
    }
}