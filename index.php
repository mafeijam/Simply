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

interface bar {
   public function call();
}

class quz {
   public function __construct(bar $bar) {
      return $bar->call();
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
//$c->instance('b', new bub('yaya'));
$b = $c->make('quz');
$bu1 = $c->make('b');
$bu2 = $c->make('b');
$k = $c->make('k');
$c->make('kii');
var_dump($b, $bu1, $bu2, $k);

