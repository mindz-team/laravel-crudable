<?php

namespace Mindz\LaravelCrudable;

use Illuminate\Support\ServiceProvider;
use Mindz\LaravelCrudable\Commands\CreateFilterCommand;

class LaravelCrudableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateFilterCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/crudable.php' => config_path('crudable.php'),
        ], 'config');
    }
}
