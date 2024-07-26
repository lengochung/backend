<?php
$namespace = 'App\Modules\Auth\Controllers';
$router->group([
	'prefix' => 'auth',
	'namespace' => $namespace
], function ($router) {
	$router -> post('login', 'AuthController@userLogin');
});
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'auth',
	'namespace' => $namespace
], function ($router) {
	$router -> post('logout', 'AuthController@userLogout');
});