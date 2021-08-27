# Laravel Crudable

This package was created to easily manage controller crudable actions and adjust responses and requests without
problems. Package also provide great way to perform search on eloquent models.

# Installation

You can install package via composer. Add repository to your composer.json

    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/mindz-team/laravel-crudable"
        }
    ],

And run

    composer require mindz-team/laravel-crudable

Publish config file

    php artisan vendor:publish  --provider="Mindz\LaravelCrudable\LaravelCrudableServiceProvider" --tag="config"

# Usage

Extend desired controller class with `CrudableController`. Class will extend your controller with methods `index`,`show`,`store`,`update`,`delete`.

Tu use it just create a route in desired location:

    Route::resource('users', UserController::class);

You can limit the controller methods using standard route limiters

    Route::resource('users', UserController::class)->only(['index','show']);
    Route::resource('users', UserController::class)->except(['index','show']);

### Model

By default crudable controller tries to discover model automatically using controller name. In example, if controller is
named `UserController` an `User` model will be looked for in configurable namespace

Default model name space is `App\Models` but you can change it in `config/crudable.php`

#### Indicate model

There is a way to avoid all that magic. If you just want to indicate a model of your desire you may use
method `getModel`

    protected function getModel() {
        return App\Models\User::class;
    }

There is one obvious rule. returned class must extend Illuminate\Database\Eloquent.

### Resources

Depends of returned type there are two possible options supported by this package. An object or collection

#### Object

Crudable methods like `show`,`store`,`update` returns an object.

Default behaviour is to discover model name based resource in namespace stored in config. Default: `App\Http\Resources`

Example: If model name used in Controller is `User` then correct resource name is  `App\Http\Resources\UserResource`

If no resource was found crudable package uses `Illuminate\Http\Resources\Json\JsonResource` to create response.

#### Indicate resource

Of course there is another way to indicate directly a resource file regardless of its name using
method `getObjectResource`

    protected function getObjectResource() {
        return App\Http\Resources\SomeOtherResource::class;
    }

Another obvious rule it that returned class must extends `Illuminate\Http\Resources\Json\JsonResource`

#### Collection

Collection is returned when using `index` crudable method.

Primary a package tries to discover appropriate class of a collection resource based on config namespace and name of
model used in controller.

Example: If an model is `User` then class `App\Http\Resources\UserCollectionResource` will be looked for.

There is a common practice that in some cases collection and object resources using same resource. Therefore if
collection resource is missing but an object collection is present it will be used.

If no object resource or collection resource was found the default behaviour is to return response in collection
using `Illuminate\Http\Resources\Json\JsonResource`.

#### Indicate resource

Of course there is another way to indicate directly a collection resource file regardless of its name using
method `getCollectionResource`

    protected function getCollectionResource() {
        return App\Http\Resources\SomeOtherCollectionResource::class;
    }

And another obvious rule it that returned class must extends `Illuminate\Http\Resources\Json\JsonResource`

### Pagination

All `index` method responses are by default paginated. Default number of items per page is `10`. This number can be
adjustable by method `paginate`

    protected function paginate() {
        return 20;
    }

To disable pagination for controller `paginate` method should return `null` ,`false` or `0`. To disable pagination for
single request a query parameter `pagination` with `false` need to be passed.

### Meta

To collection response there is a possibility to add `meta` information with method `meta`

    protected function meta() {
        return [
            "active_users"=> 10,
            "users_registered_in_last_month"=> 50,
        ];
    }

### Requests

The package also gives ability to attach a `FormRequest` to every crudable request to handle stuff like validation and
accessability. There are tho ways to use `FormRequest` while performing request.

#### Discovery

Package is able to discover `FormRequest` class based on model name and namespace set in `config/crudable.php` file.

There is a simple dependency. For example: if crudable model name is `User` then `FormRequest` classes are by default in
those locations.

| Method    | Model    | Class    | 
|--------	|-------	|-------	|
| `index`        |    `User`     |    `App\Http\Requests\Users\IndexUserRequest`            |   	
|   `show`        |    `User`        |     `App\Http\Requests\Users\ShowUserRequest`            |
|  `store`        |    `User`    |     `App\Http\Requests\Users\StoreUserRequest`            |
|  `update`        |    `User`        |     `App\Http\Requests\Users\UpdateUserRequest`            |
|  `destroy`        |    `User`        |     `App\Http\Requests\Users\DestroyUserRequest`            |

#### Indication

All fo those classes can also be indicated directly via method.

Example:

    protected function getIndexFromRequestClass() {
        return App\Http\Requests\SomeOtherRequest::class;
    }

Dependencies regarding method name shapes like this.

| Method    | Method    |  
|--------	|-------	|
|  `index`        |    `getIndexFromRequestClass`     |     	
|  `show`            |    `getShowFromRequestClass`         |  
|  `store`        |    `getStoreFromRequestClass`         |  
|  `update`        |    `getUpdateFromRequestClass`     |  
|  `destroy`        |    `getDestroyFromRequestClass`    | 

Of course must extends `Illuminate\Foundation\Http\FormRequest`

### Filter and sort

Another core feature of this package is ability to easily search and sort `index` method responses via eloquent query
builder. This can be achieved in two ways.

#### Scope Search

To use this method of search and sort your model used in crudable controller have to implement `SearchableWithScope`
interface. It will force your model to implement `scopeSearch` method where you can build your query as you like.

    class User extends Model implements SearchableWithScope {

        public function scopeSearch(Builder $query): Builder

            $query->whereNull('deleted_at);

            if(request()->has('active')){
                $query->where('active',request()->input('active'));
            }

            return $query;
        }
    }

The `scopeSearch` method must return `$query` instance.

#### Search via pipelines

Another way of search and sort is to use laravel pipelines pattern. It allows to use more abstract and decoupled
approach.

To use this method there is a necessity to implement `Searchable` interface. It will force your model to
implement `search` which returns an array of filter classes to build a query.

class User extends Model implements SearchableWithScope {

        public function search(): array
            return [
                'App\Filters\Active:class',
                'App\Filters\NotDeleted:class',
                'App\Filters\Email:class',
                'App\Filters\CreateBySort:class',
            ];
        }
    }

Every filter build small part of query.

For example `App\Filters\Email:class` should be implemented like this

    namespace App\Filters;
    
    use Closure;
    
    class Email
    {
        public function handle($request, Closure $next)
        {
            if (!request()->has('email')) {
                return $next($request);
            }
        
            return $next($request)->where('email', 'like', '%' . request()->input('email') . '%');
        }
    }

By default `Filters` classes must use `Clousure` and implement `handle` method like in example above.

Big advantage of using filters is fact that they are reusable.

To easily create a filter class use artisan command

    php artisan make:filter Active

#### Default sort

There is a possibility to use default sort which allows to sort object with no additional implementation but it is
limited only to base model fields. Any sort, regarding fields returned by relation must be separately implemented either
by scope or pipelines.

Example. If `User` model in crudable controller has an `email` field it will be sorted simple by providing `sort_by`
and `sort_direction` parameters to request

    GET http://examplehost/api/users?sort_by=email&sort_direction=asc

Parameter `sort_direction` can have `asc` or `desc` value and `sort_by` is the name of base model field.

Any more complex sorting like *sort by the count of users items* must be implemented via scope or pipeline filter.

# Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.


# Security

If you discover any security related issues, please email r.szymanski@mindz.it instead of using the issue tracker.

# Credits

Author: Roman Szymański [r.szymanski@mindz.it](mailto:r.szymanski@mindz.it)

# License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
