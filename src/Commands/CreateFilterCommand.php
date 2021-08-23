<?php

namespace Mindz\LaravelCrudable\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateFilterCommand extends Command
{
    protected $signature = 'make:filter {name}';

    protected $description = 'Create filter for pipeline search';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $client = Storage::createLocalDriver(['root' => app_path() . '/Filters/']);

        $name = $this->argument('name');

        if ($client->exists(Str::ucfirst(Str::camel($name)) . 'Filter.php')) {
            $this->error('File ' . app_path() . '/Filters/' . Str::ucfirst(Str::camel($name)) . 'Filter.php' . ' already exists');
            return;
        }

        $client->put(Str::ucfirst(Str::camel($name)) . 'Filter.php', $this->filterStub($name));

        $this->info("Filter created successfully.");
    }

    private function filterStub(string $model)
    {
        $studlyModelWithPossiblePatch = Str::ucfirst(Str::camel($model));
        $studlyModel = class_basename(Str::ucfirst(Str::camel($model)));
        $prefix = str_replace($studlyModel, '', $studlyModelWithPossiblePatch);
        $prefixEnd = $prefix ? str_replace('/','\\','\\'.rtrim($prefix,'/')) : "";
        return <<<EOT
<?php
namespace App\Filters{$prefixEnd};

use Closure;

class {$studlyModel}Filter
{
    public function handle(\$request, Closure \$next)
    {
        return \$next(\$request)->where();
    }
}
EOT;
    }
}
