<?php

namespace Simply\Interfaces;

use Closure;

interface IContainer
{
   public function bind($key, $value, $singleton = false);

   public function singleton($key, $value);

   public function with(array $args);

   public function extend($key, Closure $callback);

   public function instance($key, $object);

   public function define($key, array $args);

   public function make($key, array $args = []);

   public function call($callback, array $args = []);

   public function resolve($class, array $args = []);
}