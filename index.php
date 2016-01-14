<?php

spl_autoload_register(function($class){
   require "../$class.php";
});

use Simply\Container\Container;
$c = new Container;

class foo implements Ibar{
   public function call() {
      echo 'call me foo';
   }
}

class lulu implements Ibar {
   public function call() {

   }
}

interface Ibar {
   public function call();
}

class quz {
   public function __construct(Ibar $b, $var = 'def') {
      $this->b = $b;
      echo $var;
      return $b->call();
   }
}

class bub {
   public function __construct(foo $f, $b = 'def') {
      echo 'creating ' . $b;
   }
}

class kii {
   public function __construct(bub $b) {

   }
}

$c->bind('Ibar', 'foo');
$c->bind('b', 'bub');
$c->bind('k', function($c){
   //$b = $c->make('b', ['b' => 'yah']);
   return new kii($c['b']);
});

$b = $c->make('quz');
var_dump($b);

$c->define('quz', ['Ibar'=>'lulu']);
$qq = $c->make('quz', ['var'=>'vava']);
var_dump($qq);

$bu1 = $c->make('b', ['b' => 'bu1']);
$bu2 = $c->make('b', ['b' => 'bu2']);
$k = $c->make('k');
