<?php

namespace Mindz\LaravelCrudable\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * @method string getCollectionResource()
 * @method string getObjectResource()
 */

trait ResourceManagement
{
    private function retrieveResource($object)
    {
        if ($object instanceof Collection || $object instanceof LengthAwarePaginator) {
            return $this->getCollection($object);
        }

        if ($object instanceof Model) {
            return $this->getObject($object);
        }

        throw new \Exception('Object must be eiter instance of Collection or LengthAwarePaginator or Model');
    }

    private function getCollection($collection)
    {
        $methods = [$this->getCollectionResourceMethodName(), $this->getObjectResourceMethodName()];

        foreach ($methods as $method) {
            if (method_exists($this, $method) && $this->returnValidResourceClassType($method)) {
                $resource = $this->$method();
                return $resource::collection($collection);
            }
        }

        foreach ([true, false] as $searchForCollectionResource) {
            if (($class = $this->getResourceClassFromDefaultLocation($searchForCollectionResource)) && class_exists($class)) {
                return $class::collection($collection);
            }
        }

        return JsonResource::collection($collection);
    }

    protected function getCollectionResourceMethodName()
    {
        return 'getCollectionResource';
    }

    protected function getObjectResourceMethodName()
    {
        return 'getObjectResource';
    }

    private function returnValidResourceClassType(string $method)
    {
        if (!is_subclass_of($this->$method(), JsonResource::class)) {
            throw new \Exception(sprintf('Method %s must return class that extends Illuminate\Http\Resources\Json\JsonResource', $method));
        }

        return true;
    }

    private function getResourceClassFromDefaultLocation($collection = false)
    {
        return sprintf("%s\\%s",
            config('crudable.resources.namespace', "App\\Http\\Resources"),
            Str::ucfirst(class_basename($this->model())) . ($collection ? 'Collection' : '') . 'Resource'
        );
    }

    private function getObject($object)
    {
        $method = $this->getObjectResourceMethodName();

        if (method_exists($this, $method) && $this->returnValidResourceClassType($method)) {
            $resource = $this->$method();
            return new $resource($object);
        }

        if (($class = $this->getResourceClassFromDefaultLocation()) && class_exists($class)) {
            return new $class($object);
        }

        return new JsonResource($object);
    }
}
