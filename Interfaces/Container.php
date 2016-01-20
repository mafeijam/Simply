<?php

namespace Simply\Interfaces;

use Closure;

interface Container
{
   public function bind($key, $value, $singleton = false);

   public function singleton($key, $value);

   public function replace(Closure $callback);

   public function share($object);

   public function with(array $args);

   public function extend($key, Closure $callback);

   public function instance($key, $object);

   public function make($key, array $args = []);

   public function call($callback, array $args = []);

   public function resolve($class, array $args = []);
}