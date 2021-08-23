<?php

namespace Mindz\LaravelCrudable\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @method string getModel()
 */

trait ModelManagement
{
    protected function model(): string
    {
        $method = "getModel";

        if (method_exists($this, $method) && $this->returnValidModelClassType($method)) {
            return $this->$method();
        }

        if (($class = $this->getModelClassFromDefaultLocation()) && class_exists($class)) {
            return $class;
        }

        throw new \Exception("No model provided");
    }

    private function returnValidModelClassType(string $method): bool
    {
        if (!is_subclass_of($this->$method(), Model::class)) {
            throw new \Exception(sprintf('Method %s must return class that extends Illuminate\Database\Eloquent', $method));
        }

        return true;
    }

    private function getModelClassFromDefaultLocation(): string
    {
        return sprintf("%s\\%s",
            config('crudable.model.namespace', 'App\\Models'),
            Str::replace('Controller', '', Str::ucfirst(class_basename($this)))
        );
    }

}
