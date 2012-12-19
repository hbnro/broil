<?php

namespace Broil;

class Config
{

  private static $bag = array(
                    // urls
                    'root' => '/',
                    'rewrite' => FALSE,
                    'index_file' => 'index.php',
                    // routing
                    'request_uri' => '/',
                    'request_method' => 'GET',
                    // about server
                    'server_base' => '',
                    'tld_size' => 0,
                  );



  public static function set($key, $value = NULL)
  {
    static::$bag[$key] = $value;
  }

  public static function get($key, $default = FALSE)
  {
    return isset(static::$bag[$key]) ? static::$bag[$key] : $default;
  }

}
