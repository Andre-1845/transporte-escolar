<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        if ($user->hasRole('super_admin')) {
            return;
        }

        if ($user->school_id) {
            $builder->where(
                $model->qualifyColumn('school_id'),
                $user->school_id
            );
        }
    }
}
