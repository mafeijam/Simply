<?php

namespace Simply\Container;

use Closure;
use Exception;
use ArrayAccess;
use ReflectionClass;
use Simply\Interfaces\IContainer;

class Container implements IContainer, ArrayAccess
{
   protected $bindings = [];
   protected $instances = [];

   public function bind($key, $value, $singleton = false)
   {
      if (isset($this->bindings[$key]) or isset($this->instances[$key])) {
         throw new Exception("key $key has already been bound");
      }

      $this->bindings[$key] = [$value, $singleton];
   }

   public function singleton($key, $value)
   {
      return $this->bind($key, $value, true);
   }

   public function instance($key, $object)
   {
      if (isset($this->bindings[$key]) or isset($this->instances[$key])) {
         throw new Exception("key $key has already been bound");
      }

      $this->instances[$key] = $object;
   }

   public function make($key, array $args = [])
   {
      if (isset($this->instances[$key])) {
         return $this->instances[$key];
      }

      if (isset($this->bindings[$key])) {
         list($class, $singleton) = $this->bindings[$key];

         $object = $class instanceof Closure ? $class($this) : $this->resolve($class, $args);

         if ($singleton) {
            $this->instances[$key] = $object;
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

      $dependencies = $this->getDependencies($parameters);
      $finalize = array_merge($dependencies, $args);

      return $reflector->newInstanceArgs($finalize);
   }

   protected function getDependencies($parameters)
   {
      $dependencies = [];
      foreach ($parameters as $parameter) {
         if ($class = $parameter->getClass()) {
            if ($class->isInterface()) {
               $class = $this->findInterfaceBinding($class->name);
               $dependencies[] = $class instanceof Closure ? $class($this) : $this->resolve($class);
               continue;
            }
            $dependencies[] = $this->resolve($class->name);
         }

         if ($parameter->isDefaultValueAvailable()) {
            $dependencies[$parameter->name] = $parameter->getDefaultValue();
         } else {
            throw new Exception("unable to resolve $parameter");
         }
      }
      return $dependencies;
   }

   protected function findInterfaceBinding($key)
   {
      if (isset($this->bindings[$key])) {
         return $this->bindings[$key][0];
      }

      throw new Exception("key $key hasn't been bound in the container");
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
      return isset($this->bindings[$key]);
   }

   public function offsetUnset($key)
   {
      unset($this->bindings[$key], $this->instances[$key]);
   }
}
