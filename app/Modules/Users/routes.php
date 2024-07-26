<?php
$namespace = 'App\Modules\Users\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'users',
	'namespace' => $namespace
], function ($router) {
	$router -> post('user-info', 'UsersController@getUserInfo')->name('userInfo');
	$router -> post('save-profile', 'UsersController@onUpdateUserProfile');
	$router -> post('all-list', 'UsersController@getAllList');
	$router -> post('list-user-ids', 'UsersController@getUserListByUserIds');
	$router -> post('user-union-group', 'UsersController@getUserListUnionGroupList');
	$router -> post('save', 'UsersController@onSave');
	$router -> post('reset-password', 'UsersController@onResetPassword');
	$router -> post('delete', 'UsersController@onDelete');
});
