# IOC container
Lightweight dependency injection container with laravel like autowiring, interface/abstract class binding and contextual binding. PSR-11 compliant.

*************

## Usage examples

`require '../vendor/autoload.php';`

`$container = new Foundation\Container\Container();`

#### Binding/Registering services

A service (bound value) can be a string representation of a class, an anonymous function which returns an object instance, or an object instance itself. If you bind an object instance itself, that service will essentially act as a singleton because the same instance will always be returned.

`$container->bind(Foo\Bar::class, Foo\Bar::class);

$container->bind(Foo\Bar::class, function ($container) { 
   return new \Foo\Bar($container['Foo\Baz']);
});

$container->bind('Foo\Bar', FooBar::class);

$container->bind(Foo\Bar::class, new \Foo\Bar());`

For convenience you can map your services to keys and there are multiple ways of doing it.

Directly with the bind statement
`$container->bind('foo', function ($container) {
   return new Foo\Bar();
});`

or with mappedTo method

`$container->bind(Foo\Bar::class, Foo\Bar::class)->mappedTo('foo');`

or with key method

`$container->('foo', Foo\Bar::class);`

#### Retrieveing services

`$service = $container['Foo\Bar'];`
`$service = $container[Foo\Bar::class];`
`$service = $container['foo'];`

#### Binding/Registering multiple services
If you want 


##### Mapping services to keys


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







