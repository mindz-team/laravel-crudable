<?php

namespace Mindz\LaravelCrudable\Traits;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

trait RequestManagement
{
    public function applyRequest(): null|FormRequest
    {
        $method = sprintf("get%sFromRequestClass", Str::ucfirst(Route::getCurrentRoute()->getActionMethod()));

        if (($baseRequestClass = $this->getBaseRequestClass()) && class_exists($baseRequestClass)) {
            app($baseRequestClass);
        }

        if (method_exists($this, $method) && $this->returnValidRequestClassType($method)) {
            return app($this->$method());
        }

        if (($class = $this->getRequestClassFromDefaultLocation()) && class_exists($class)) {
            return app($class);
        }

        return null;
    }

    private function returnValidRequestClassType(string $method): bool
    {
        if (!is_subclass_of($this->$method(), FormRequest::class)) {
            throw new \Exception(sprintf('Method %s must return class that extends Illuminate\Foundation\Http\FormRequest', $method));
        }

        return true;
    }

    private function getRequestClassFromDefaultLocation(): string
    {
        return sprintf("%s\\%s\\%s",
            config('crudable.request.namespace', "App\\Http\\Requests"),
            Str::plural(class_basename($this->model())),
            Str::ucfirst(Route::getCurrentRoute()->getActionMethod()) . class_basename($this->model()) . 'Request'
        );
    }

    private function getBaseRequestClass()
    {
        return sprintf("%s\\%s", config('crudable.base_request.namespace', "App\\Http\\Requests"), 'BaseRequest');
    }
}
