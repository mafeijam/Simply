<?php

spl_autoload_register(function($class){
   require "../$class.php";
});

require 'test.php';

use Simply\Container\Container;
$c = new Container;

$c->bind('Ibar', 'foo');
$c->make('qoo');
$c->make('huu');