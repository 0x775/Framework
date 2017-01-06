<?php

class BaseController {
    protected $Version="1.17.0";
	public static function version(){
        return $this->Version;
    }
	
    public function __construct(){
        //TODO
    }

    public function Config($conf){
        $res=include BASE_PATH.'/config/'.$conf.'.php';
        return (object)$res;
    }

}

class Controller {
    public function __construct(){
        parent::__construct();

        $this->loader = new Twig_Loader_Filesystem(__DIR__.'/../views');
        $this->twig = new Twig_Environment($this->loader, array(
              //'cache' => __DIR__.'/../app/storage/views',
              'auto_reload' => true
        ));

        $function = new Twig_SimpleFunction('route', function($url) {
            $uri = array_search($url,Route::$asRoutes);

            $arr=func_get_args();
            array_shift($arr);
            
            $pattern='/\(.*?\)/';
            while (preg_match($pattern,$uri)) {
                $uri=preg_replace($pattern,array_shift($arr), $uri,1);
            }
            return $uri;
        });
		$static_url=new Twig_SimpleFunction('static_url', function($path) {
            return '/static/'.$path;
        });
		$static_domain=new Twig_SimpleFunction('static_domain', function($path) {
			$static_uri = $path;
			if(strpos($path,"http") ===0) return $static_uri;
			
			$confObj=$this->Config('config');
			if(property_exists($confObj, 'static_domain')) $static_uri = $confObj->static_domain.$path;
            return $static_uri;
        });
		$json_encode=new Twig_SimpleFunction('json_encode', function($data) {
            return json_encode($data);
        });
		
        $this->twig->addFunction($function);
		$this->twig->addFunction($static_url);
		$this->twig->addFunction($static_domain);
		$this->twig->addFunction($json_encode);
    }

    public function render($template_name,$array=array()){
        $html=$this->twig->render($template_name, $array);
		echo $html;
    }	
	
    public function Config($conf){
        $res=include BASE_PATH.'/config/'.$conf.'.php';
        return (object)$res;
    }
	
    public function locals($var=null, $exc=null){
        if(!$var) $var = get_defined_vars();
        if (!$exc) $exc = array('GLOBALS', '_FILES', '_COOKIE', '_POST', '_GET','request');
        $vars = array();
        foreach ($var as $key => $value) {
            if (!in_array($key, $exc))
                $vars[$key] = $value;
        }
        return $vars;
    }
}
