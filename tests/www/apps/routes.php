<?php

//首页
Route::get('','TestController@index');
Route::get('/a','TestController@b');


Route::dispatch();
?>
