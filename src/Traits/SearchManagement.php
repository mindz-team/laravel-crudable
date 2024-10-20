<?php

namespace Mindz\LaravelCrudable\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Pipeline;
use Mindz\LaravelCrudable\Http\Interfaces\Searchable;
use Mindz\LaravelCrudable\Http\Interfaces\SearchableWithScope;

trait SearchManagement
{
    protected $defaultPagination = 10;

    private function results()
    {
        $model = $this->model();

        $query = $model::query();

        $object = new $model;

        if ($object instanceof Searchable) {
            $query = $this->pipelineSearch($query, $object);
        }

        if ($object instanceof SearchableWithScope) {
            $query = $this->searchUsingScope($query);
        }

        return $this->defaultSortAndPagination($query);
    }

    protected function pipelineSearch($query, $object)
    {
        return app(Pipeline::class)
            ->send($query)
            ->through($object->searchFilters())
            ->thenReturn();
    }

    private function defaultSortAndPagination($query)
    {
        if (!$this->alreadySorted($query) && request()->get('sort_by') && request()->get('sort_direction')) {
            $query->orderBy(request()->get('sort_by', 'id'), request()->get('sort_direction', 'desc'));
        }

        if ((method_exists($this, 'pagination') && !$this->pagination()) || (request()->has('pagination') && !filter_var(request()->get('pagination'), FILTER_VALIDATE_BOOLEAN))) {
            return $query->get();
        }

        $query = $query->paginate(request()->get('length', (method_exists($this, 'pagination') ? $this->pagination() : $this->defaultPagination)), ['*'], 'page', request()->get('page', 0));

        if (method_exists($this, 'withQueryString') && $this->withQueryString()) {
            $query->withQueryString();
        }

        return $query;
    }

    protected function searchUsingScope($query)
    {
        return $query->search();
    }

    private function alreadySorted(Builder $query): bool
    {
        $baseOrder = $query->getQuery();

        return isset($baseOrder) && !empty($baseOrder->orders);
    }
}
