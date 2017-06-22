<?php

namespace Ethereal\Database;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    /**
     * Parse a list of relations into individuals. This modifier allows for ['relationName' => ['columns']].
     *
     * @param array $relations
     *
     * @return array
     */
    protected function parseWithRelations(array $relations)
    {
        foreach ($relations as $name => $constraints) {
            if (is_array($constraints)) {
                $relations[$name] = function ($query) use ($constraints) {
                    /** @var Builder|\Illuminate\Database\Query\Builder $query */
                    $query->select($constraints);
                };
            }
        }

        return parent::parseWithRelations($relations);
    }
}
