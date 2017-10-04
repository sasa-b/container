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

##### Mapping services to keys
For convenience you can map your services to keys and there are multiple ways of doing it.

1. With mapTo method via method chaining

```php
$container->bind(Foo\Bar::class, Foo\Bar::class)->mappedTo('foo');
```

2. With the keys method
```php
$container->('foo', Foo\Bar::class);
```

3. Directly with the bind statement
```php
$container->bind('foo', function ($container) {
   return new Foo\Bar();
});
```

When you directly bind services to a key, they will only be accessible by that key name and those services can't be used for **autowiring (injecting by type hinting)**, unless the service you are binding is a string representaton of a class, then that service will automatically be mapped to the key. This does not apply to `mapTo` and `key` methods. 


#### Retrieveing services

```php
$service = $container['Foo\Bar'];
$service = $container[Foo\Bar::class];
$service = $container['foo'];
```

#### Binding/Registering multiple services
If you want 




1. If you are binding a string representation of a class to a key, that service will automatically be mapped to it.
`$container->bind('date', \DateTime::class);`

To retrieve the service just call
`$container['date']` 
or 
`$container->get('date')` 
or 
`$container->date()`;


#### Abstract binding
You can bind/register services to interfaces and abstract classes as well. These abstract bindings are convenient for autowiring. type hint your dependencies in your method declarations

because you are not type hinting with concretions but with interfaces, and abstract classes. these bindings to , a.k.a autowiring. 
 
`$container->bind(Foo\BarInterface::class, Foo\Bar::class)`
`$container->bind(Foo\AbstractBaz::class, Foo\Baz::class)`







