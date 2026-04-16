<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SearchableTrait
{
    public function scopeSearch(Builder $query, ?string $search): Builder
    {

        if (! $search) {
            return $query;
        }

        $fields = property_exists($this, 'searchable') ? array_filter((array) $this->searchable) : [];

        if ($fields === []) {
            return $query;
        }

        $term = '%'.addcslashes($search, '%_\\').'%';

        return $query->where(function ($q) use ($fields, $term) {
            foreach ($fields as $field) {
                $q->orWhere((string) $field, 'like', $term);
            }
        });
    }
}
