<?php
$namespace = 'App\Modules\NoticeUsers\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'notice-users',
	'namespace' => $namespace
], function ($router) {
	$router -> post('list', 'NoticeUsersController@getAllList');
	$router -> post('save', 'NoticeUsersController@onSave');
	$router -> post('detail', 'NoticeUsersController@getDetail');
});
