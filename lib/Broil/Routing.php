<?php

namespace Broil;

class Routing
{

  private static $map = array();
  private static $routes = array();
  private static $grouped = array();

  private static $allowed = array('anchor', 'static', 'locals', 'subdomain');


  public static function add($method, $match, $to, array $params = array())
  {
    $params = array_merge(compact('match', 'to'), $params);
    $test   = end(static::$grouped) ?: array();
    $params = array_merge(array(
      'constraints' => array(),
      'before'      => array(),
      'after'       => array(),
      'match'       => '*any',
      'root'        => '/',
      'to'          => '',
    ), $params, $test);


    $params['match'] = $params['root'] . rtrim($params['match'], '/');
    $params['match'] = preg_replace('/\/{2,}/', '/', $params['match']);

    if ( ! empty($params['path'])) {
      $test = array();

      foreach (array('match', 'subdomain') as $key) {
        isset($params[$key]) && $test[$key] = $params[$key];
      }

      static::$map[$params['path']] = $test;
    }

    if ( ! isset(static::$routes[$method])) {
      static::$routes[$method] = array();
    }
    static::$routes[$method] []= $params;
  }

  public static function mount(\Closure $group, array $params = array())
  {
    (static::$grouped []= $params) && $group();
    array_pop(static::$grouped);
  }

  public static function path($for, array $vars = array())
  {
    if ( ! empty(static::$map[$for])) {
      $params = static::$map[$for];
      $params['action'] = $params['match'];
    } else {
      $params['action'] = "/$for";
    }


    foreach (static::$allowed as $key) {
      if (array_key_exists($key, $vars)) {
        $params[$key] = $vars[$key];
        unset($vars[$key]);
      }
    }


    $out = \Broil\Helpers::build($params);
    $out = strtr($out, $vars);

    do {
      $tmp = $out;
      $out = preg_replace('/(?<!:)\/{2,}/', '/', $out);
      $out = preg_replace('/\([^()]*?\)|\/?\*\w+/', '', $out);
    } while($tmp <> $out);

    return $out;
  }

  public static function sub()
  {
    @list(,, $base) = explode('/', \Broil\Config::get('server_base'));

    if ($base) {
      $max = \Broil\Config::get('tld_size');
      $set = explode('.', $base);

      $old = array_slice($set, -($max + 1));
      $new = array_diff($set, $old);

      return join('.', $new);
    }
    return FALSE;
  }

  public static function run()
  {
    $method = \Broil\Config::get('request_method');
    $route  = \Broil\Config::get('request_uri');

    if (($test = static::sub()) !== FALSE) {
      $sub = $test;
    }


    // TODO: do testing please...
    if ( ! empty(static::$routes[$method])) {
      foreach (static::$routes[$method] as $params) {
        if (isset($sub, $params['subdomain'])) {
         if ($sub <> $params['subdomain']) {
           continue;
         }
        }


        $regex = \Broil\Helpers::compile($params['match'], $params['constraints']);

        if (preg_match("/^$regex$/", $route, $matches)) {
          $vars = array();

          foreach ($matches as $key => $val) {
            is_numeric($key) OR $vars[$key] = $val;
          }

          $params['params'] = $vars;
          return $params;
        }
      }
    }

    throw new \Exception("Route '$method $route' not found");
  }

  public static function all()
  {
    return static::$routes;
  }

}
