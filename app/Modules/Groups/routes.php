<?php
$namespace = 'App\Modules\Groups\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'groups',
	'namespace' => $namespace
], function ($router) {
	$router -> post('list', 'GroupsController@getAllList');
	$router -> post('delete', 'GroupsController@onDelete');
	$router -> post('save', 'GroupsController@onSave');
	$router -> post('cancel', 'GroupsController@onCancel');
	$router -> post('group-info', 'GroupsController@getGroupDetail');
	$router -> post('send-approval', 'GroupsController@onSendApproval');
	$router -> post('approval', 'GroupsController@onApproval');
	$router -> post('reject', 'GroupsController@onReject');
});
