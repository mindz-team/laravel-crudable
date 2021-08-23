<?php

namespace Mindz\LaravelCrudable\Http\Interfaces;

use Illuminate\Database\Eloquent\Builder;

interface SearchableWithScope
{
    public function scopeSearch(Builder $query): Builder;
}
