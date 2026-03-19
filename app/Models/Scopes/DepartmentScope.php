<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DepartmentScope implements Scope
{
    /**
     * Flag to prevent recursion.
     *
     * @var bool
     */
    protected static $applying = false;

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (static::$applying) {
            return;
        }

        static::$applying = true;

        try {
            if (auth()->check()) {
                $user = auth()->user();

                // Load department if not already loaded to check for "Admin" name
                // Use withoutGlobalScopes to avoid recursion if Department model had it too
                if (!$user->relationLoaded('department')) {
                    $user->setRelation('department', $user->department()->withoutGlobalScopes()->first());
                }

                // Global Admin bypasses the scope (checked via department named 'Admin')
                if ($user->department && $user->department->name === 'Admin') {
                    return;
                }

                // Other users can only see records belonging to their department
                if ($user->department_id) {
                    $builder->where($model->getTable() . '.department_id', $user->department_id);
                } else {
                    // If user somehow doesn't have a department, they shouldn't see anything
                    $builder->where($model->getTable() . '.id', '<', 0);
                }
            }
        } finally {
            static::$applying = false;
        }
    }
}
