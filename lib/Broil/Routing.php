<?php

namespace Broil;

class Routing
{

  private static $map = array();
  private static $routes = array();
  private static $grouped = array();


  public static function add($method, $match, $to, array $params = array()) {
    $params = array_merge(compact('match', 'to'), $params);
    $test   = end(static::$grouped) ?: array();
    $params = array_merge(array(
      'constraints' => array(),
      'before'      => array(),
      'after'       => array(),
      'match'       => '/*any',
      'root'        => '/',
      'to'          => '',
    ), $params, $test);


    ($params['root'] <> '/') && $params['match'] = $params['root'] . rtrim($params['match'], '/');

    if ( ! empty($params['path'])) {
      $test = array();

      foreach (array('match', 'subdomain') as $key) {
        isset($params[$key]) && $test[$key] = $params[$key];
      }

      static::$map[$params['path']] = $test;
      unset($params['path']);
    }

    if ( ! isset(static::$routes[$method])) {
      static::$routes[$method] = array();
    }
    static::$routes[$method] []= $params;
  }

  public static function mount(\Closure $group, array $params = array()) {
    (static::$grouped []= $params) && $group();
    array_pop(static::$grouped);
  }

  public static function path($for, array $vars = array()) {
    if ( ! empty(static::$map[$for])) {
      $params = static::$map[$for];
      $params['action'] = $params['match'];
    } else {
      $params['action'] = strtr($for, '_', '/');
    }

    $out = Helpers::build($params);
    $out = strtr($out, $vars);

    do {
      $tmp = $out;
      $out = preg_replace('/\([^()]*?\)|\/?\*\w+/', '', $out);
    } while($tmp <> $out);

    return $out;
  }

  public static function run() {
    $domain      = Config::get('domain');
    $subdomain   = Config::get('subdomain');
    $server_name = Config::get('server_name');

    @list($sub_test) = explode($domain, $server_name);

    $route   = Config::get('request_uri');
    $method  = Config::get('request_method');


    if ( ! empty(static::$routes[$method])) {
      foreach (static::$routes[$method] as $params) {
        if (isset($params['subdomain'])) {
          $test = $params['subdomain'] ?: $subdomain;
          if ($test <> trim($sub_test, '.')) {
            continue;
          }
        }


        $regex = Helpers::compile($params['match'], $params['constraints']);

        if (preg_match("/^$regex$/", $route, $matches)) {
          $vars = array();

          foreach ($matches as $key => $val) {
            is_numeric($key) OR $vars[$key] = $val;
          }

          $params['params'] = $vars;
          return $params;
        }
      }
    } else {
      // TODO: raise exception
    }
    // TODO: raise exception
  }

}
