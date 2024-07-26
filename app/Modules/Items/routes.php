<?php
$namespace = 'App\Modules\Items\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'items',
	'namespace' => $namespace
], function ($router) {
	$router -> post('list', 'ItemsController@getAllList');
    $router -> post('items-dropdown', 'ItemsController@getItemDropdown');
    $router -> post('detail', 'ItemsController@getDetail');
});
