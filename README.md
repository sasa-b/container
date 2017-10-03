# IOC container
Lightweight dependency injection container with laravel like autowiring, interface/abstract class binding and contextual binding. PSR-11 compliant.

*************

## Usage examples

`require '../vendor/autoload.php';

$container = new Foundation\Container\Container();`

### Binding/Registering services

`$container->bind(Foo\Bar::class, function ($container) { 
   return new \Foo\Bar($container['Foo\Baz']);
});

$container->bind('date', \DateTime::class);

$container->bind(Foundation\Request\Cookie::class, Foundation\Request\Cookie::class);`

#### Mapping services to keys
For convenience you can map your services to keys and there are multiple ways of doing it.

1. If you are binding the full namespaced class name to a key value, that service will automatically be mapped to it.
`$container->bind('date', \DateTime::class);`
To retrieve the service just call
`$container['date']` or `$container->get('date')` or `$container->date()`;

### Binding/Registering multiple services

 
`$container->register([
    Foundation\Request\Http::class => Foundation\Request\Http::class,
    Foundation\Sessions\SessionManager::class => \Foundation\Sessions\SessionManager::class,
    Foundation\Request\Cookie::class => Foundation\Request\Cookie::class,
    'date' => \DateTime::class,
    Foundation\Core\Database::class => Foundation\Core\Database::class,
    /*Foundation\Database\QueryBuilder::class => (function ($c) {
        return new \Foundation\Database\PDOQuery($c['db']);
    })*/
])->mapTo(['request', 'session', 'cookie', 'db'])->share(['session', 'db', Foundation\Core\Database::class]);

//



//$container->bind(\Foundation\Database\ConnectorInterface::class, \Foundation\Database\PDOConnector::class);
$container->bind(\Foundation\Database\QueryBuilder::class, \Foundation\Database\PDOQuery::class);
$container->bind(\Foundation\Database\ConnectorInterface::class, \Foundation\Database\mysqliConnector::class);
$container->bind(\Foundation\Database\ConnectorInterface::class, function () {
    return new \Foundation\Database\PDOConnector();
}, 'cookie')->share();

//$container->has();

//$container->get();

