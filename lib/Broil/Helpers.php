<?php

namespace Broil;

class Helpers
{

  private static $tokens = array(
                    // escapes
                    '/\)/' => ')?',
                    '/\(/' => '(?:',
                    '/\//' => '\\/',
                    '/\.(?![+*?])/' => '\\.',
                    // captures
                    '/(?<!\?)\*([a-z_][a-z\d_]*?)(?=\b)/i' => '(?<\\1>.*)',
                    '/(?<!\?):([a-z_][a-z\d_]*?)(?=\b)/i' => '(?<\\1>[%\w+-]+)',
                  );

  public static function build($url, array $params = array())
  {
    if (is_array($url)) {
      $params = array_merge($url, $params);
    } elseif ( ! isset($params['action'])) {
      $params['action'] = $url;
    }

    $params = array_merge(array(
      'action' => '',
      'anchor' => '',
      'prefix' => '',
      'static' => FALSE,
      'locals' => array(),
    ), $params);

    $root   = \Broil\Config::get('root');
    $index  = \Broil\Config::get('index_file');
    $server = \Broil\Config::get('server_base');

    if (isset($params['subdomain'])) {
      $prefix = ! empty($params['prefix']) ? $params['prefix'] : $params['subdomain'];
      $server = static::reduce($server, $prefix);
    }

    if ($params['static']) {
      return "$server$root$params[action]";
    } else {
      $anchor =
      $query  = '';
      $link   = "$server$root$index";

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

      \Broil\Config::get('rewrite') && $link = str_replace("$index/", '', $link);

      $params['anchor'] && $anchor = $params['anchor'];

      $link .= $query ? "?$query" : '';
      $link .= $anchor ? "#$anchor" : '';

      return $link;
    }
  }

  public static function compile($expr, array $constraints = array())
  {
    $expr = preg_replace(array_keys(static::$tokens), array_values(static::$tokens), $expr);

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

  public static function reduce($host, $sub = FALSE)
  {
    @list(,, $base) = explode('/', $host);

    if ($base) {
      $set = explode('.', $base);
      $max = \Broil\Config::get('tld_size');

      $set = array_slice($set, -($max + 1));
      $sub && array_unshift($set, $sub);

      $host = str_replace($base, join('.', $set), $host);
    }

    return $host;
  }

}
