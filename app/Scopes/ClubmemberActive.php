<?php

namespace App\Scopes;

use Statamic\Query\Scopes\Filter;

class ClubmemberActive extends Filter
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
        return __('ClubmemberActive');
    }

    /**
     * Define the filter's field items.
     *
     * @return array
     */
    public function fieldItems()
    {
        return [
            'isactive' => [
                'type' => 'radio',
                'options' => [
                    'true' => __('Active'),
                    'false' => __('NotActive'),
                ]
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
        $query->where('is_active', $values['isactive'] === 'true');

        // app(DepartmentScope::class)->apply($query, [
        //     'departments::dept-wt',
        // ]);
    }

    /**
     * Define the applied filter's badge text.
     *
     * @param array $values
     * @return string
     */
    public function badge($values)
    {
        return $values['isactive'] === 'true'
            ? __('Active')
            : __('NotActive');
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
