<?php
use Illuminate\Database\Capsule\Manager as Capsule;

//定义 BASE_PATH
define('BASE_PATH', __DIR__);

//Autoload 自动载入
require '../vendor/autoload.php';

// TIME_ZONE
date_default_timezone_set('Asia/Shanghai');

//error_reporting(E_ALL ^ E_NOTICE);

//whoops 错误提示
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

//Eloquent ORM
$capsule = new Capsule;
$capsule->addConnection(require BASE_PATH.'/config/database.php');
$capsule->setAsGlobal();
$capsule->bootEloquent();

?>
