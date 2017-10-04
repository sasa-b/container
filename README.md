# IOC container
Lightweight dependency injection container with laravel like autowiring, interface/abstract class binding and contextual binding. PSR-11 compliant.

*************

## Usage examples

```php
require '../vendor/autoload.php';

$container = new Foundation\Container\Container();`
```

#### Binding/Registering services

A service (bound value) can be a string representation of a class, an anonymous function which returns an object instance, or an object instance itself. If you bind an object instance itself, that service will essentially act as a singleton because the same instance will always be returned.

```php
$container->bind(Foo\Bar::class, Foo\Bar::class); 

$container->bind(Foo\Bar::class, function ($container) { 
   return new \Foo\Bar($container['Foo\Baz']);
   
}); 

$container->bind('Foo\Bar', FooBar::class); 

$container->bind(Foo\Bar::class, new \Foo\Bar());
```
##### Singletons

```php
   $container->bind('Foo\Bar', FooBar::class)->share();
   
   //or
   
   $container->bind('Foo\Bar', FooBar::class);
   $container->share(Foo\Bar::class);
   
   $container->bind(Foo\Baz::class, Foo\Baz::class)->mapTo('baz');
   $container->share('baz');
```


#### Mapping services to keys
For convenience you can map your services to keys and there are multiple ways of doing it.

1. With mapTo method via method chaining

```php
$container->bind(Foo\Bar::class, Foo\Bar::class)->mapTo('foo');
```

2. With the key method
```php
$container->key('foo', Foo\Bar::class);
```

3. Directly with the bind statement
```php
$container->bind('foo', function ($container) {
   return new Foo\Bar();
});
```

When you directly bind services to a _key_, they will only be accessible by that _key_ and those services can't be used for **autowiring (injecting by type hinting)**, unless the service you are binding/registering is a string representaton of a class, in that case the service will automatically be mapped to the given _key_. 

In case of `mapTo` and `key` bound/registered services are accessible by both the key and their class name, and because of that they can be used for autowiring. 

#### Binding/Registering multiple services
For registering multiple services at once you can use the `register` method or it's alias `bindMany`.

```php
$container->register([
    Foundation\Request\Http::class => Foundation\Request\Http::class,
    Foundation\Sessions\SessionManager::class => \Foundation\Sessions\SessionManager::class,
    Foundation\Request\Cookie::class => Foundation\Request\Cookie::class,
    'date' => \DateTime::class,
    Foundation\Core\Database::class => Foundation\Core\Database::class,
    Foundation\Database\QueryBuilder::class => (function ($c) {
        return new \Foundation\Database\PDOQuery($c['db']);
    })
])->mapTo(['request', 'session', 'cookie', 'db']);

$container->register([
    Foundation\Request\Http::class => Foundation\Request\Http::class,
    Foundation\Sessions\SessionManager::class => \Foundation\Sessions\SessionManager::class,
    Foundation\Request\Cookie::class => Foundation\Request\Cookie::class
]);

$container->keys([
   'request' => Foundation\Request\Http::class => Foundation\Request\Http::class,
   'session' => Foundation\Sessions\SessionManager::class,
   'cookie' => Foundation\Request\Cookie::class
]);
```

##### Registering multiple singletons

```php
/* Only those specified will be registered as singletons */
$container->register([
    Foundation\Request\Http::class => Foundation\Request\Http::class,
    Foundation\Sessions\SessionManager::class => \Foundation\Sessions\SessionManager::class,
    Foundation\Request\Cookie::class => Foundation\Request\Cookie::class,
    'date' => \DateTime::class,
    Foundation\Core\Database::class => Foundation\Core\Database::class,
    Foundation\Database\QueryBuilder::class => (function ($c) {
        return new \Foundation\Database\PDOQuery($c['db']);
    })
])->share(['session', 'db', Foundation\Core\Database::class]);

/* All will be registered as singletons */
$container->register([
    Foundation\Request\Http::class => Foundation\Request\Http::class,
    Foundation\Sessions\SessionManager::class => \Foundation\Sessions\SessionManager::class,
    Foundation\Request\Cookie::class => Foundation\Request\Cookie::class,
    'date' => \DateTime::class,
    Foundation\Core\Database::class => Foundation\Core\Database::class,
    Foundation\Database\QueryBuilder::class => (function ($c) {
        return new \Foundation\Database\PDOQuery($c['db']);
    })
])->share();
// or
$container->register([
    Foundation\Request\Http::class => Foundation\Request\Http::class,
    Foundation\Sessions\SessionManager::class => \Foundation\Sessions\SessionManager::class,
    Foundation\Request\Cookie::class => Foundation\Request\Cookie::class,
    'date' => \DateTime::class,
    Foundation\Core\Database::class => Foundation\Core\Database::class,
    Foundation\Database\QueryBuilder::class => (function ($c) {
        return new \Foundation\Database\PDOQuery($c['db']);
    })
], true);

```

#### Abstract binding
You can bind/register services to interfaces and abstract classes as well. These abstract bindings are convenient when used with autowiring, because you can type hint with abstractions (abstract classes and interfaces) and not concretions (implementations).

```php
$container->bind(Foo\BarInterface::class, Foo\Bar::class);
$container->bind(Foo\AbstractBaz::class, Foo\Baz::class);
```

##### Contextual binding
When you want to bind/register multiple different services to a same interface or abstract class you need to provide _context_ otherwise one will override the other. _Context_ is the third parameter to the `bind` method, and it can be a string representation of a class or a key mapped to a class, and that class needs to be the one in whose constructor or method the interface/abstract class is used as a typehint.

```php
// This will result in an override and `Foo\Baz::class` will always be returned for Foo\Bar::interface
$container->bind(Foo\BarInterface::class, Foo\Bar::class);
$container->bind(Foo\BarInterface::class, Foo\Baz::class);

$container->bind(Foo\BarInterface::class, Foo\Bar::class);
$container->bind(Foo\BarInterface::class, Foo\Baz::class, 'FooController');
```

#### Retrieveing services

```php
$service = $container['Foo\Bar'];
$service = $container[Foo\Bar::class];
$service = $container['foo'];

// Only works for services mapped to keys
$service = $container->foo();

// Retrieves a new instance or a singleton if a service has been registered as a singleton
$service = $container->get('foo');

// Always retrieves a singleton
$service = $container->shared('foo');
```






