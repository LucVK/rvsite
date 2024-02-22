<?php

namespace App\Scopes;

use Statamic\Query\Scopes\Filter;

class DepartmentFilter extends Filter
{
    /**
     * Pin the filter.
     *
     * @var bool
     */
    public $pinned = true;

    /**
     * Define the filter's title.
     *
     * @return string
     */
    public static function title()
    {
        return __('DepartmentFilter');
    }

    /**
     * Define the filter's field items.
     *
     * @return array
     */
    public function fieldItems()
    {
        return [
            'departments' => [
                'type' => 'terms',
                'taxonomies' => ['departments'],
                'mode' => 'select',
                'create' => false,
            ]
        ];
    }

    /**
     * Apply the filter.
     *
     * @param \Statamic\Query\Builder $query
     * @param array $values
     * @return void
     */
    public function apply($query, $values)
    {
        app(DepartmentScope::class)->apply($query, $values['departments']);
    }

    /**
     * Define the applied filter's badge text.
     *
     * @param array $values
     * @return string
     */
    public function badge($values)
    {
        return implode('/',  $values['departments']);
    }

    /**
     * Determine when the filter is shown.
     *
     * @param string $key
     * @return bool
     */
    public function visibleTo($key)
    {
        return $key === 'entries' && $this->context['collection'] == 'clubmembers';
    }
}
