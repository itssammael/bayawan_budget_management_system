<?php

namespace App\Traits;

trait HasDepartmentScope
{
    /**
     * Boot the trait and apply the global scope.
     *
     * @return void
     */
    protected static function bootHasDepartmentScope()
    {
        static::addGlobalScope(new \App\Models\Scopes\DepartmentScope);
    }
}
