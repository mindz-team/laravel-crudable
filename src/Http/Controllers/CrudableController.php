<?php

namespace Mindz\LaravelCrudable\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Mindz\LaravelCrudable\Traits\ModelManagement;
use Mindz\LaravelCrudable\Traits\RequestManagement;
use Mindz\LaravelCrudable\Traits\ResourceManagement;
use Mindz\LaravelCrudable\Traits\SearchManagement;

class CrudableController extends Controller
{
    use RequestManagement, ModelManagement, ResourceManagement, SearchManagement;

    public function index()
    {
        $this->applyRequest();

        $resource = $this->retrieveResource(
            $this->results()
        );

        if (method_exists($this, 'meta')) {
            $resource->additional(['meta' => $this->meta()]);
        }

        return $resource;
    }

    public function store()
    {
        return DB::transaction(function () {
            $request = $this->applyRequest() ?? request();

            return $this->retrieveResource(
                $this->model()::create($request->all())
            );
        });
    }

    public function show($objectId)
    {
        $this->applyRequest();

        $object = $this->model()::findOrFail($objectId);

        return $this->retrieveResource($object);
    }

    public function update($objectId)
    {
        $request = $this->applyRequest() ?? request();

        $object = $this->model()::findOrFail($objectId);

        return DB::transaction(function () use ($object, $request) {
            return $this->retrieveResource(
                tap($object)->update($request->all())
            );
        });
    }

    public function destroy($objectId)
    {
        $this->applyRequest();

        $object = $this->model()::findOrFail($objectId);

        DB::transaction(function () use ($object) {
            $object->delete();
        });

        return response()->noContent();
    }

}
