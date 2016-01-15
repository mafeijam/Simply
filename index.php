<?php

spl_autoload_register(function($class){
   require "../$class.php";
});

use Simply\Container\Container;
$c = new Container;

class foo implements Ibar{
   public $foo;

   public function call() {
      echo ' call me foo';
   }
   public function set($var) {
      $this->foo = $var;
      return $this;
   }
   public function qoo(qoo $q, $b) {
      var_dump($q, $b);
   }
}

class qoo {

}

class lulu implements Ibar {
   public function call() {
      echo ' call me lulu';
   }
}

interface Ibar {
   public function call();
}

class quz {
   public function __construct(Ibar $b, $var = 'def', $v2) {
      $this->b = $b;
      echo $var . $v2;
      return $b->call();
   }
}

class bub {
   public function __construct(foo $f, $b) {
      echo 'creating ' . $b;
   }
}

class kii {
   public function __construct(bub $b) {

   }
}

class ff {
   public function __construct(foo $f) {
      var_dump($f);
   }
}

$c->bind('Ibar', 'foo');
$c->share(function($c){
   return (new foo)->set(123);
});

$c->make('quz', ['v2'=>'--jijij']);

$c->call('foo@qoo', [123]);

$c->make('ff');
$c->make('ff');
/*
$c->bind('Ibar', 'foo');
$c->bind('b', 'bub');
$c->bind('k', function($c){
   //$b = $c->make('b', ['b' => 'yah']);
   return new kii($c->make('b', ['b' => 'yah']));
});

$b = $c->make('quz');
var_dump($b);

$c->define('quz', ['Ibar'=>'lulu']);
$qq = $c->make('quz', ['var'=>'vava']);
var_dump($qq);

$bu1 = $c->make('b', ['b' => 'bu1 ']);
$bu2 = $c->make('b', ['b' => 'bu2 ']);
$k = $c->make('k');
*/



