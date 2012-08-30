<?php

namespace Broil;

class Helpers
{

  public static function build($url, array $params = array()) {
    if (is_array($url)) {
      $params = array_merge($url, $params);
    } elseif ( ! isset($params['action'])) {
      $params['action'] = $url;
    }

    $params  = array_merge(array(
      'action' => '',
      'anchor' => '',
      'locals' => array(),
      'host'   => FALSE,
    ), $params);


    if (isset($params['subdomain'])) {
      $domain    = Config::get('domain');
      $subdomain = Config::get('subdomain');

      @list($sub_test) = explode($domain, Config::get('server_name'));

      $host = "//$domain";
      $test = $params['subdomain'] ?: $subdomain;

      $test && $host = str_replace('//', "//$test.", $host);

      $params['host'] = $host;
    }


    $root    = Config::get('root');
    $index   = Config::get('index_file');
    $rewrite = (boolean) Config::get('rewrite');

    $base    = $params['host'] ? $params['host'] . $root : $root;
    $link    = $base . $index;

    $anchor  =
    $query   = '';

    if ( ! empty($params['action'])) {
      @list($part, $anchor) = explode('#', $params['action']);
      @list($part, $query)  = explode('?', $part);

      $link .= $part;
    }

    if ( ! empty($params['locals'])) {
      $test = array();
      $hash = uniqid('__PREFIX__');

      parse_str($query, $test);

      $query = http_build_query(array_merge($test, $params['locals']), $hash, '&amp;');
      $query = preg_replace("/{$hash}\d+=/", '', $query);
    }

    $rewrite && $link = str_replace("$index/", '', $link);

    $params['anchor'] && $anchor = $params['anchor'];

    $link .= $query ? "?$query" : '';
    $link .= $anchor ? "#$anchor" : '';

    return $link;
  }

  public static function compile($expr, array $constraints = array()) {
    static $tokens = array(
              '/\//' => '\\/',
              '/\(/' => '(?:',
               '/\)/' => '|)',
              '/\*([a-z_][a-z\d_]*?)(?=\b)/i' => '(?<\\1>.+?)',
              '/:([a-z_][a-z\d_]*?)(?=\b)/i' => '(?<\\1>[^\/#&?]+?)',
            );


    $expr = preg_replace(array_keys($tokens), array_values($tokens), $expr);

    if (is_array($constraints)) {
      $test = array();

      foreach ($constraints as $key => $value) {
        $item  = preg_replace('/\W/', '', $key);
        $value = str_replace('/', '\\/', $value);
        $expr  = str_replace($key, $item ? "(?<$item>$value)" : $value, $expr);
      }
    }

    return $expr;
  }

}
