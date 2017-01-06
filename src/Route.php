<?php

class Route {
  public $prefix;
  public static $groupStack = array();
  public static $halts = false;
  public static $routes = array();
  public static $asRoutes =array();
  public static $methods = array();
  public static $callbacks = array();
  public static $patterns = array(
      ':any' => '[^/]+',
      ':num' => '[0-9]+',
      ':all' => '.*'
  );
  public static $error_callback;

  /**
   * Defines a route w/ callback and method
   */
  public static function __callstatic($method, $params) {
    //$uri = dirname($_SERVER['PHP_SELF']).$params[0];
    $uri = $params[0];
    $uri = isset(self::$groupStack['prefix']) ? self::$groupStack['prefix'].$uri : $uri;
    //var_dump($params);exit;
	
	//统一去掉最后的/
	if(substr($uri, 0-1)=='/') $uri=rtrim($uri,'/');

    if(is_array($params[1])){
      $callback = $params[1]['uses'];
      self::$asRoutes[$uri]=$params[1]['as'];
    }
    else{
        $callback = $params[1];
    }
    
    array_push(self::$routes, $uri);
    array_push(self::$methods, strtoupper($method));
    array_push(self::$callbacks, $callback);
  }

  //fix add.group
  public static function group(array $attributes,Closure $callback){
    self::$groupStack = $attributes;
    call_user_func($callback);
    self::$groupStack = array();
  }

  //fix add.resource (REST)
  public static function resource($uri,$params) {
    self::__callstatic('GET',array($uri,$params.'@index')); 
    self::__callstatic('GET',array($uri.'/create',$params.'@create'));  
    self::__callstatic('GET',array($uri.'/show/(:num)',$params.'@show')); 
    self::__callstatic('POST',array($uri,$params.'@store'));  
    self::__callstatic('GET',array($uri.'/edit/(:num)',$params.'@edit'));   
    self::__callstatic('POST',array($uri.'/save/(:num)',$params.'@save')); 
    self::__callstatic('POST',array($uri.'/delete/(:num)',$params.'@delete'));
  }

  /**
   * Defines callback if route is not found
  */
  public static function error($callback) {
    self::$error_callback = $callback;
  }

  public static function haltOnMatch($flag = true) {
    self::$halts = $flag;
  }

  /**
   * Runs the callback for the given request
   */
  public static function dispatch(){
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];

    $searches = array_keys(static::$patterns);
    $replaces = array_values(static::$patterns);

    $found_route = false;

    self::$routes = str_replace('//', '/', self::$routes);

	//统一去掉最后的/
	if(substr($uri, 0-1)=='/') $uri=rtrim($uri,'/');
    //echo $uri;
    //echo $method;
    //var_dump(self::$routes);
    //var_dump(self::$callbacks);
    //exit;

    // Check if route is defined without regex
    if (in_array($uri, self::$routes)) {
      $route_pos = array_keys(self::$routes, $uri);
      foreach ($route_pos as $route) {
        // Using an ANY option to match both GET and POST requests
        if (self::$methods[$route] == $method || self::$methods[$route] == 'ANY') {
          $found_route = true;

          // If route is not an object
          if (!is_object(self::$callbacks[$route])) {

            // Grab all parts based on a / separator
            $parts = explode('/',self::$callbacks[$route]);

            // Collect the last index of the array
            $last = end($parts);

            // Grab the controller name and method call
            $segments = explode('@',$last);
            //var_dump($segments);exit;

            // Instanitate controller
            $controller = new $segments[0]();

            // Call method
            $controller->{$segments[1]}();

            if (self::$halts) return;
          } else {
            // Call closure
            call_user_func(self::$callbacks[$route]);

            if (self::$halts) return;
          }
        }
      }
    } else {
      // Check if defined with regex
      $pos = 0;
      foreach (self::$routes as $route) {
        if (strpos($route, ':') !== false) {
          $route = str_replace($searches, $replaces, $route);
        }

        if (preg_match('#^' . $route . '$#', $uri, $matched)) {
          if (self::$methods[$pos] == $method) {
            $found_route = true;

            // Remove $matched[0] as [1] is the first parameter.
            array_shift($matched);

            if (!is_object(self::$callbacks[$pos])) {

              // Grab all parts based on a / separator
              $parts = explode('/',self::$callbacks[$pos]);

              // Collect the last index of the array
              $last = end($parts);

              // Grab the controller name and method call
              $segments = explode('@',$last);

              // Instanitate controller
              $controller = new $segments[0]();

              // Fix multi parameters
              if(!method_exists($controller, $segments[1])) {
                echo "controller and action not found";
              } else {
                call_user_func_array(array($controller, $segments[1]), $matched);
              }

              if (self::$halts) return;
            } else {
              call_user_func_array(self::$callbacks[$pos], $matched);

              if (self::$halts) return;
            }
          }
        }
        $pos++;
      }
    }

    // Run the error callback if the route was not found
    if ($found_route == false) {
      if (!self::$error_callback) {
        self::$error_callback = function() {
          //header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
          //echo '404';
		  header("HTTP/1.1 404 Not Found");  
		  header("Status: 404 Not Found");
        };
      }
      call_user_func(self::$error_callback);
    }
  }
}


?>