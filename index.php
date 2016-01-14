<?php

spl_autoload_register(function($class){
   require "../$class.php";
});

use Simply\Container\Container;
$c = new Container;

class foo implements bar{
   public function call() {
      return 'call me foo';
   }
}

class lulu implements bar {
   public function call() {

   }
}

interface bar {
   public function call();
}

class quz {
   public function __construct(bar $b) {
      $this->b = $b;
      return $b->call();
   }
}

class bub {
   public function __construct($b) {
      echo 'creating ' . $b;
   }
}

class kii {
   public function __construct(bub $b) {

   }
}

$c->bind('bar', 'foo');
$c->singleton('b', function(){
   return new bub('buuu');
});
$c->bind('k', function($c){
   return new kii($c['b']);
});

$b = $c->make('quz');
$bu1 = $c->make('b');
$bu2 = $c->make('b');
$k = $c->make('k');
$c->define('quz', ['bar'=>'lulu']);

$qq = $c->make('quz');
var_dump($qq);