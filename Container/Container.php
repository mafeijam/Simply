<?php

namespace Simply\Container;

use Closure;
use Exception;
use ArrayAccess;
use ReflectionClass;
use ReflectionParameter;
use Simply\Interfaces\IContainer;

class Container implements IContainer, ArrayAccess
{
   protected $bindings = [];
   protected $instances = [];
   protected $definitions = [];
   protected $args = [];

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

   public function with(array $args)
   {
      end($this->bindings);
      $key = key($this->bindings);
      $this->args[$key] = $args;
   }

   public function instance($key, $object)
   {
      $this->checkUnique($key);

      $this->instances[$key] = $object;
   }

   public function define($key, array $definition)
   {
      $value = $key;

      if (is_array($key)) {
         $value = current($key);
         $key = key($key);
      }

      $this->definitions[$key] = $definition;
      $this->bind($key, $value);
      return $this;
   }

   public function make($key, array $args = [])
   {
      if (isset($this->instances[$key]))
         return $this->instances[$key];

      if (isset($this->args[$key]) and empty($args))
         $args = $this->args[$key];

      if (isset($this->definitions[$key]))
         return $this->resolveDefined($this->definitions[$key], $key, $args);

      if (isset($this->bindings[$key])) {
         list($class, $singleton) = $this->bindings[$key];

         $object = $class instanceof Closure ? $class($this) : $this->resolve($class, $args);

         if ($singleton) {
            $this->instances[$key] = $object;
            unset($this->bindings[$key]);
         }

         return $object;
      }

      return $this->resolve($key, $args);
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

   protected function resolveDefined($definition, $class, array $args = [])
   {
      $class = $this->bindings[$class][0];

      $reflector = new ReflectionClass($class);

      $dependencies = $this->getDependencies($reflector->getConstructor()->getParameters(), $args, $definition);

      return $reflector->newInstanceArgs($dependencies);
   }

   protected function getDependencies($parameters, $args, $definition = null)
   {
      $dependencies = [];

      foreach ($parameters as $parameter) {
         if ($class = $parameter->getClass()) {
            if ($class->isInterface()) {
               $class = $this->findInterfaceBinding($class->name, $definition);
               $dependencies[] = $class instanceof Closure ? $class($this) : $this->resolve($class);
               continue;
            }
            $dependencies[] = $this->resolve($class->name);
            continue;
         }
         $dependencies[] = $this->resolveParameter($parameter, $args);
      }

      return $dependencies;
   }

   protected function resolveParameter(ReflectionParameter $parameter, $args)
   {
      if (isset($args[$parameter->name]))
         return $args[$parameter->name];

      if ($parameter->isDefaultValueAvailable())
         return $parameter->getDefaultValue();

      throw new Exception("unable to resolve $parameter in class {$parameter->getDeclaringClass()->getName()}");
   }

   protected function findInterfaceBinding($key, $definition = null)
   {
      if (isset($definition) and key($definition) == $key)
         return current($definition);

      if (isset($this->bindings[$key]))
         return $this->bindings[$key][0];

      throw new Exception("key $key hasn't been bound in the container");
   }

   protected function checkUnique($key)
   {
      if (isset($this->bindings[$key]) or isset($this->instances[$key]))
         throw new Exception("key [$key] has already been bound");
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
      unset($this->bindings[$key], $this->instances[$key], $this->args[$key], $this->definitions[$key]);
   }

   public function __get($key)
   {
      return $this->make($key);
   }
}
