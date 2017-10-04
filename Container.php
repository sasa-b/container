<?php
/**
 * Created by PhpStorm.
 * User: sasablagojevic
 * Date: 12/31/16
 * Time: 12:49 AM
 */

namespace Foundation\Container;


use Foundation\Container\Exceptions\ContainerException;
use Foundation\Container\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ArrayAccess;
use Closure;

class Container implements ArrayAccess, ContainerInterface
{
    /**
     * @var Container instance
     */
    protected static $instance;

    /**
     * Instances of the registered singletons
     *
     * @var array
     */
    protected $shared = [];

    /**
     * Registered singletons
     *
     * @var string
     */
    protected $singletons = [];

    /**
     * Bindings for Container (dynamic properties)
     * Containes callables, classes or instances mapped to class names or keys
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * TO DO:
     * bind classes to interfaces
     */

    /**
     * Keys which services are mapped to
     * [
     *      'session' => '\Foundation\Sessions\SessionManager',
     *      'request' => '\Foundation\Request\Http
     * ]
     * @var array
     */
    protected $keys = [];

    /**
     * Resolved dependencies (constructor and method agruments)
     *
     * @var null|array
     */
    public $resolved;

    /**
     * @var array[ReflectionClass]
     */
    protected $reflections = [];

    /**
     * Last binding/s to the container
     *
     * @var mixed
     */
    protected $last_binding;


    public function __construct()
    {
        static::setInstance($this);
    }

    /**
     * @return mixed
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * @param Container $container
     */
    public static function setInstance(Container $container)
    {
        static::$instance = $container;
    }

    /**
     * Return the service mapped to a key
     *
     * @param $service
     * @return mixed
     */
    protected function map($service)
    {
        return isset($this->keys[$service]) ? $this->keys[$service] : $service;
    }

    /**
     * @param $class
     * @param array|null $params
     * @return mixed
     */
    public function instance($class, array $params = null)
    {
        $reflection = $this->reflect($class, '__construct');

        /**
         * Static Class
         */
        if (!$reflection->isAbstract() && !$reflection->isInterface() && !$reflection->isInstantiable()) {
            return $class;
        }

        if (isset($this->resolved[$class]['__construct'])) {
            $args = $this->resolved[$class]['__construct'];

            if ($params) {
                $args = array_merge($args, $params);
            }

            $dependencies = $this->instantiateDependencies($reflection, $args);

            //TO DO: add optional params for class __constructor
            //because ReflectClass::newInstanceArgs passes arguments positionally, so it's the same
            return new $class(...array_values($dependencies));
        }

        return new $class();
    }

    /**
     * @param ReflectionClass $reflection
     * @param array $dependencies
     * @return array
     */
    public function instantiateDependencies(ReflectionClass $reflection, array $dependencies)
    {
        foreach ($dependencies as $key => $value) {

            if (is_string($value)) {

                if (interface_exists($value)) {
                   $dependencies[$key] = $this->make($this->getAbstractBinding($value, $reflection));
                }

                if (class_exists($value)) {
                    $dependency = new ReflectionClass($value);

                    if ($dependency->isAbstract()) {
                        $dependencies[$key] = $this->make($this->getAbstractBinding($value, $reflection));
                    } else {
                        $dependencies[$key] = $this->make($value);
                    }
                }
            }
        }

        return $dependencies;
    }

    /**
     * Check if service is abstract class or interface
     *
     * @param $service
     * @return bool
     */
    protected function isAbstract($service)
    {
        return interface_exists($service) || (class_exists($service) && $this->reflection($service)->isAbstract());
    }

    /**
     * @param $service
     * @return bool
     */
    protected function isConcrete($service)
    {
        return !$this->isAbstract($service);
    }
    /**
     * Instantiate dependencies that are bound to abstract classes and interfaces
     *
     * @param string $service
     * @param ReflectionClass $reflection
     * @return mixed
     */
    protected function getAbstractBinding(string $service, ReflectionClass $reflection)
    {
        if (isset($this->bindings[$service. '|' .$reflection->getName()])) {
            return $service. '|' .$reflection->getName();
        } else if (isset($this->bindings[$service. '|' .$reflection->getShortName()])) {
            return $service. '|' .$reflection->getShortName();
        }
        return $service;
    }

    /**
     * @param $class
     * @param $method
     * @param $params
     * @return mixed
     */
    public function call($class, $method, $params)
    {
        if (is_object($class)) {
            $class_instance = $class;
            $class = get_class($class_instance);
        } else {
            $class_instance = $this->instance($class);
        }

        $reflection = $this->reflect($class, $method);

        if (isset($this->resolved[$class][$method])) {
            $dependencies = $this->instantiateDependencies($reflection, $this->resolved[$class][$method]);
            $params = $params ? array_merge($dependencies, $params) : $dependencies;
        }
        //return $reflection->getMethod($method)->invokeArgs($class_instance, $params);
        try {
            return call_user_func_array(array($class_instance, $method), $params);
        } catch (\BadMethodCallException $e) {
            throw $e;
        }
    }

    /**
     * @param $class
     * @param $method
     * @return ReflectionClass
     */
    protected function reflect($class, $method) {
        $reflection = $this->reflection($class);

        if (!isset($this->resolved[$class]) || !isset($this->resolved[$class][$method])) {
            if ($reflection->hasMethod($method)) {
                if ($params = $reflection->getMethod($method)->getParameters()) {
                    foreach ($params as $param) {
                        if ($dependency = $param->getClass()) {
                            $this->resolved[$class][$method][$dependency->name] = $dependency->name;
                        } elseif ($param->isDefaultValueAvailable()) {
                            $this->resolved[$class][$method][$param->name] = $param->getDefaultValue();
                        } else {
                            $this->resolved[$class][$method][$param->name] = null;
                        }
                    }
                }
            }
        }

        return $reflection;
    }

    /**
     * @param $service
     * @return bool
     */
    protected function isSingleton($service)
    {
        return isset($this->singletons[$service]);
    }

    /**
     * @param $class
     * @return mixed
     */
    public function singleton($class)
    {
        if (!isset($this->shared[$class])) {
            $this->shared[$class] = $this->make($class);
        }

        return $this->shared[$class];
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
   public function __call($name, $arguments)
   {
       if ($this->offsetExists($name)) {
           return $this[$name];
       }
   }

    /**
     * @param $service
     * @return mixed
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function make($service)
    {
        try {

            if (is_callable($this->bindings[$service]) && $this->bindings[$service] instanceof Closure) {
                return $this->bindings[$service]($this);
            } else if (is_object($this->bindings[$service])) {
                return $this->bindings[$service];
            }

            return $this->instance($this->bindings[$service]);

        } catch (\Exception $e) {

            if (!$this->has($service)) {
                throw new NotFoundException("No service with [$service] name or key is registered.", $e->getCode());
            } else {
                throw new ContainerException($e->getMessage(), $e->getCode());
            }
        }

    }

    /* ArrayAccess Interface methods - START */

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        $this->bind($offset, $value);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) {
        $service = $this->map($offset);
        return isset($this->bindings[$service]) || isset($this->shared[$service]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        $service = $this->map($offset);

        if ($this->isSingleton($service)) {
            unset($this->shared[$service], $this->resolved[$service], $this->singletons[$service]);
        }

        if (isset($this->reflections[$service])) {
            unset($this->reflections[$service]);
        }

        unset($this->bindings[$service], $this->resolved[$service]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        $service = $this->map($offset);

        if ($this->isSingleton($service)) {
            return $this->singleton($service);
        }

        return $this->make($service);
    }
    /* ArrayAccess Interface methods - END */

    /**
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }

    /**
     * Check if a service was bound/registered to the container
     *
     * @param string $service
     * @return bool
     */
    public function has($service)
    {
        return $this->offsetExists($this->map($service));
    }

    /**
     * Get an instance or a singleton instance of a bound/registered service
     *
     * @param string $service
     * @return mixed
     */
    public function get($service)
    {
        $service = $this->map($service);

        return $this[$service];
    }

    /**
     * Always get a singleton instance of a service
     *
     * @param $service
     * @return mixed
     */
    public function shared($service)
    {
        return $this->singleton($service);
    }

    /**
     * Stores and retrieves Reflection instances for classes
     *
     * @param $service
     * @return ReflectionClass
     */
    protected function reflection($service)
    {
        if (!isset($this->reflections[$service])) {
            $this->reflections[$service] = new ReflectionClass($service);
        }

        return $this->reflections[$service];
    }

    /**
     * Checks if it is a simple key binding
     *
     * @param string $key
     * @return bool
     */
    protected function isKeyBinding(string $key)
    {
        return is_string($key) && !class_exists($key) && !interface_exists($key);
    }

    /**
     * Checks if a key is registered
     *
     * @param $key
     * @return bool
     */
    public function hasKey($key)
    {
        return isset($this->keys[$key]);
    }

    /**
     * Map key to service or get the service mapped to a specific key
     *
     * @param string $key
     * @param string $service
     * @return mixed|null
     */
    public function key(string $key = null, string $service = null)
    {
        if ($key && $service) {
            $this->keys[$key] = $service;
            return;
        }

        if ($key) {
            return $this->hasKey($key) ? $this->keys[$key] : null;
        }

        if ($service) {
            foreach ($this->keys as $k => $s) {
                if ($s === $service) {
                    return $k;
                }
            }
            return;
        }
    }

    /**
     * Map keys to services or get mapped keys
     *
     * @param array $keys
     * @return array
     */
    public function keys(array $keys = null)
    {
        if (!$keys) {
            return $this->keys;
        }

        foreach ($keys as $k => $v) {
            $this->key($k, $v);
        }
    }

    /**
     * Set a key for the last registered service
     *
     * @param array|string $key
     * @return $this
     */
    public function mapTo($key)
    {
        if ($this->last_binding) {

            if (is_array($key) && is_array($this->last_binding)) {
                $i = 0;
                foreach ($this->last_binding as $service => $binding) {
                    if (class_exists($service)) {
                        if (isset($key[$i])) {
                            $this->key($key[$i], $service);
                        }
                        $i++;
                    }
                }
                return $this;
            }

            if (is_array($this->last_binding)) {
                $service = array_keys($this->last_binding[count($this->last_binding) - 1])[0];
            } else {
                $service = $this->last_binding;
            }

            if (class_exists($service)) {
                $this->key($key, $service);
            }
        }

        return $this;
    }

    /**
     * Bind/register multiple services to the container
     *
     * @param array $bindings
     * @param bool $shared
     * @return $this
     */
    public function register(array $bindings, $shared = false)
    {
        foreach ($bindings as $service => $binding) {
            $this->bind($service, $binding);
        }

        $this->last_binding = $bindings;

        if ($shared) {
            $this->share($bindings);
        }

        return $this;
    }

    /**
     * Alias for register
     *
     * @param array $bindings
     * @param $shared
     * @return Container
     */
    public function bindMany(array $bindings, $shared)
    {
        return $this->register($bindings, $shared);
    }

    /**
     * Bind/register a service to the container
     *
     * @param $service
     * @param $binding
     * @param null $context
     * @return $this
     * @throws \Foundation\MissingTagException
     */
    public function bind($service, $binding, $context = null)
    {
        /**
         * If $service is a key and $binding is a string representation of a class
         */
        if ($this->isKeyBinding($service) && !$this->hasKey($service) && !is_object($binding)) {
            $this->key($service, $binding);
            $service = $binding;
        }

        if ($this->isAbstract($service)) {
            if (isset($this->bindings[$service]) && $context) {
                $this->bindings["$service|".$this->map($context)] = $binding;
                return $this;
            }
        }

        $this->bindings[$service] = $binding;

        $this->last_binding = $service;

        return $this;
    }

    /**
     * Bind/register a services as singletons
     *
     * @param string|array|null $services
     */
    public function share($services = null)
    {
        if (!$services && $this->last_binding) {
            if ($this->isConcrete($this->last_binding)) {
                $services = $this->last_binding;
                $this->last_binding = null;
            }
        }

        if (is_array($services)) {
            foreach ($services as $service) {
                $this->singletons[$this->map($service)] = 1;
            }
        } else if (is_string($services)) {
            $this->singletons[$this->map($services)] = 1;
        }
    }

    /**
     * Remove a service from the container
     *
     * @param $service
     */
    public function remove($service)
    {
        unset($this[$service]);
    }

    /**
     * Empty the container
     */
    public function flush()
    {
        $this->bindings = [];
        $this->singletons = [];
        $this->shared = [];
        $this->keys = [];
        $this->reflections = [];
    }

    /**
     * Return an array with registered services
     *
     * @return array
     */
    public function bindings()
    {
        return $this->bindings;
    }
}