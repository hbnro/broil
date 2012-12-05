<?php

namespace Broil;

class Routing
{

  private static $map = array();
  private static $routes = array();
  private static $grouped = array();


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


    $parts = array($params['root'], trim($params['match'], '/'));
    $params['match'] = join('/', array_filter($parts));

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
      $params['action'] = strtr("_$for", '_', '/');
    }

    $out = \Broil\Helpers::build($params);
    $out = strtr($out, $vars);

    do {
      $tmp = $out;
      $out = preg_replace('/\([^()]*?\)|\/?\*\w+/', '', $out);
    } while($tmp <> $out);

    return $out;
  }

  public static function run() {
    $domain      = \Broil\Config::get('domain');
    $subdomain   = \Broil\Config::get('subdomain');
    $server_name = \Broil\Config::get('server_name');

    $route   = \Broil\Config::get('request_uri');
    $method  = \Broil\Config::get('request_method');


    $sub_test = FALSE;

    if ($domain) {
      @list($sub_test) = explode($domain, $server_name);
    }


    if ( ! empty(static::$routes[$method])) {
      foreach (static::$routes[$method] as $params) {
        if ($sub_test && isset($params['subdomain'])) {
          $test = $params['subdomain'] ?: $subdomain;
          if ($test <> trim($sub_test, '.')) {
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
