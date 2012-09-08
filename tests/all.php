<?php

require dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

// TODO: more tests about subdomain/domain/host options

Broil\Config::set('request_uri', '/foo/fuck-yeah12');
Broil\Config::set('request_method', 'POST');

Broil\Routing::add('GET', '/', 'callback', array('path' => 'home'));
Broil\Routing::add('POST', '/foo(/:candy+id(/*bar)?)?', function () {
    echo 'OK';
  }, array(
      'path' => 'bar',
      'constraints' => array(
        '+id' => '\d+',
      ),
    ));


Broil\Routing::mount(function () {
  Broil\Routing::add('PUT', '/z', 'foo#bar', array('path' => 'candy'));
  }, array(
    'root' => '/x/y',
  ));

var_dump(Broil\Routing::run());
var_dump(Broil\Routing::path('bar', array('+id' => 99)));
var_dump(Broil\Routing::path('home'));
var_dump(Broil\Routing::path('candy'));
