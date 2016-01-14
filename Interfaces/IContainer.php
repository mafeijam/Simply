<?php

namespace Simply\Interfaces;

interface IContainer
{
   public function bind($key, $value, $singleton = false);

   public function singleton($key, $value);

   public function instance($key, $object);

   public function make($key, array $args = []);

   public function resolve($class, array $args = []);
}