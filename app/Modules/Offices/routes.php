<?php
$namespace = 'App\Modules\Offices\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'offices',
	'namespace' => $namespace
], function ($router) {
	$router -> post('list', 'OfficesController@getAllList');
});
