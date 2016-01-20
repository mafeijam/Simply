<?php

interface Ibar {
   public function call();
}

class foo implements Ibar {
   public function call() {
       var_dump($this);
    }
}

class luu implements Ibar {
   public function call() {
       var_dump($this);
   }
}

class juu implements Ibar {
   public function call() {
       var_dump($this);
   }
}

class qoo {
   protected $var;
   protected $h;

   public function __construct(Ibar $bar) {
      $bar->call();
   }

   public function set(huu $h, $var) {
      $this->var = $var;
      $this->h = $h;
      echo '--setting';
      var_dump($this);
      return $this;
   }
}

class huu {
  public function __construct(Ibar $bar) {
      $bar->call();
  }
}

class zaa {
  public function __construct($var) {
    var_dump($var);
    var_dump($this);
  }
}