<?php

namespace App\Scopes;

use Statamic\Query\Scopes\Scope;

class DepartmentScope extends Scope
{
    /**
     * Apply the scope.
     *
     * @param \Statamic\Query\Builder $query
     * @param array $values
     * @return void
     */
    public function apply($query, $values)
    {
        $query->whereTaxonomyIn($values);
    }
}
