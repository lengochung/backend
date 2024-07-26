<?php
$namespace = 'App\Modules\Roles\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'roles',
	'namespace' => $namespace
], function ($router) {
	$router -> post('list', 'RolesController@getAllList');
	$router -> post('save', 'RolesController@onSave');
	$router -> post('delete', 'RolesController@onDelete');
	$router -> post('user', 'RolesController@getRoleCurrentUser');
});
