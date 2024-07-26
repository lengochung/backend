<?php
$namespace = 'App\Modules\Functions\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'functions',
	'namespace' => $namespace
], function ($router) {
	$router -> post('list', 'FunctionsController@getAllList');
});
