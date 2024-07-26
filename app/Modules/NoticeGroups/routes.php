<?php
$namespace = 'App\Modules\NoticeGroups\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'notice-groups',
	'namespace' => $namespace
], function ($router) {
	$router -> post('list', 'NoticeGroupsController@getAllList');
	$router -> post('save', 'NoticeGroupsController@onSave');
	$router -> post('detail', 'NoticeGroupsController@getDetail');
});
