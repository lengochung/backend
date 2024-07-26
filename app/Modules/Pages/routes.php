<?php
$namespace = 'App\Modules\Pages\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'pages',
	'namespace' => $namespace
], function ($router) {
	$router -> post('list', 'PagesController@getAllList');
});
