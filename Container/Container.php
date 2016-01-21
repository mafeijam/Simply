<?php

namespace Simply\Container;

use Closure;
use Exception;
use ArrayAccess;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use Simply\Interfaces\Container as ContainerInterface;

class Container implements ContainerInterface, ArrayAccess
{
   protected $bindings = [];
   protected $instances = [];
   protected $args = [];
   protected $extenders = [];
   protected $shares = [];
   protected $sharesArgs = [];
   protected $enableOverride = false;

   protected static $instance;

   public function bind($key, $value, $singleton = false)
   {
      $this->checkUnique($key);
      $this->bindings[$key] = [$value, $singleton];
      return $this;
   }

   public function singleton($key, $value)
   {
      return $this->bind($key, $value, true);
   }

   public function replace(Closure $callback)
   {
      $this->enableOverride = true;
      $callback($this);
      $this->enableOverride = false;
      return $this;
   }

   public function share($object, array $args = [])
   {
      if (is_string($object)) {
         $this->shares[$object] = $object;
         $this->sharesArgs[$object] = $args;
         return;
      }

      if ($object instanceof Closure)
         $object = $object($this);

      $this->shares[get_class($object)] = $object;
   }

   public function with(array $args)
   {
      end($this->bindings);
      $this->args[key($this->bindings)] = $args;
   }

   public function extend($key, Closure $callback)
   {
      if (isset($this->instances[$key]))
         $this->instances[$key] = $callback($this->instances[$key], $this);

      $this->extenders[$key][] = $callback;
   }

   public function instance($key, $object)
   {
      $this->checkUnique($key);
      $this->instances[$key] = $object;
   }

   public function make($key, array $args = [])
   {
      if (isset($this->instances[$key]))
         return $this->instances[$key];

      if (isset($this->args[$key]) and empty($args))
         $args = $this->args[$key];

      if (isset($this->bindings[$key])) {
         list($class, $singleton) = $this->bindings[$key];

         $object = $class instanceof Closure ? $class($this) : $this->resolve($class, $args);

         $this->runExtenders($object, $key);

         if ($singleton) {
            $this->instances[$key] = $object;
            unset($this->bindings[$key]);
         }

         return $object;
      }

      return $this->resolve($key, $args);
   }

   public function call($callback, array $args = [], $closure = false)
   {
      $reflector = $this->getReflector($callback);

      $dependencies = $this->getDependencies($reflector->getParameters(), $args);

      if ($closure) {
         return function () use ($callback, $dependencies) {
            return call_user_func_array($callback, $dependencies);
         };
      }

      return call_user_func_array($callback, $dependencies);
   }

   protected function getReflector(&$callback)
   {
      if (is_string($callback)) {
         list($class, $method) = explode('@', $callback);
         $callback = [$this->make($class), $method];
         return new ReflectionMethod($class, $method);
      }

      return new ReflectionFunction($callback);
   }

   public function resolve($class, array $args = [])
   {
      $reflector = new ReflectionClass($class);

      $constructor = $reflector->getConstructor();
      if (is_null($constructor))
         return new $class;

      $parameters = $constructor->getParameters();
      if (empty($parameters))
         return new $class;

      return $reflector->newInstanceArgs($this->getDependencies($parameters, $args));
   }

   protected function getDependencies(array $parameters, array $args)
   {
      $dependencies = [];

      foreach ($parameters as $parameter) {
         if ($class = $parameter->getClass()) {
            if ($class->isInterface()) {
               $interface = $this->resolveInterface($class->name, $args, $parameter);
               $dependencies[] = $this->parseInterface($interface);
               continue;
            }

            if ($this->isShare($class->name)) {
               $dependencies[] = $this->shares[$class->name];
               continue;
            }

            $dependencies[] = $this->make($class->name);
            continue;
         }
         $dependencies[] = $this->resolveParameter($parameter, $args);
      }

      return $dependencies;
   }

   protected function resolveParameter(ReflectionParameter $parameter, array &$args)
   {
      if (isset($args[$parameter->name]))
         return $args[$parameter->name];

      if (array_values($args) === $args)
         return array_shift($args);

      if ($parameter->isDefaultValueAvailable())
         return $parameter->getDefaultValue();

      $class = $parameter->getDeclaringClass()->getName();
      $method = $parameter->getDeclaringFunction()->getShortName();

      throw new Exception("unable to resolve parameter \${$parameter->getName()} in class [$class] method [$method]");
   }

   protected function resolveInterface($key, array $args, ReflectionParameter $parameter)
   {
      if (isset($args[$parameter->name]))
         return $args[$parameter->name];

      if (isset($args[$key]))
         return $args[$key];

      if (isset($this->instances[$key]))
         return $this->instances[$key];

      if (isset($this->bindings[$key]))
         return $this->bindings[$key][0];

      throw new Exception("unable to resolve interface [$key]");
   }

   protected function parseInterface($interface)
   {
      if (is_object($interface))
         return $interface;

      return $interface instanceof Closure ? $interface($this) : $this->make($interface);
   }

   protected function runExtenders(&$object, $key)
   {
      if (isset($this->extenders[$key])) {
         foreach ($this->extenders[$key] as $extender) {
            $object = $extender($object, $this);
         }
      }
   }

   protected function isShare($class)
   {
      if (isset($this->shares[$class]) and is_string($this->shares[$class]))
         $this->shares[$class] = $this->resolve($this->shares[$class], $this->sharesArgs[$class]);

      return array_key_exists($class, $this->shares);
   }

   protected function checkUnique($key)
   {
      if (isset($this[$key]) and $this->enableOverride === false)
         throw new Exception("key [$key] has already been bound");
   }

   protected static function setInstance($instance)
   {
      static::$instance = $instance;
   }

   public static function getInstance()
   {
      return static::$instance;
   }

   public function offsetGet($key)
   {
      return $this->make($key);
   }

   public function offsetSet($key, $value)
   {
      return $this->bind($key, $value);
   }

   public function offsetExists($key)
   {
      return isset($this->bindings[$key]) or isset($this->instances[$key]);
   }

   public function offsetUnset($key)
   {
      unset($this->bindings[$key], $this->instances[$key], $this->args[$key]);
   }

   public function __get($key)
   {
      return $this[$key];
   }

   public function __set($key, $value)
   {
      $this[$key] = $value;
   }
}