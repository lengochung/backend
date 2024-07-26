<?php
$namespace = 'App\Modules\NoticesComments\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'notices-comments',
	'namespace' => $namespace
], function ($router) {
	$router -> post('list', 'NoticesCommentsController@getAllList');
	$router -> post('save', 'NoticesCommentsController@onSave');
	$router -> post('update', 'NoticesCommentsController@onUpdate');
	$router -> post('detail', 'NoticesCommentsController@getDetail');
	$router -> post('delete', 'NoticesCommentsController@onDelete');
    $router -> post('upload', 'NoticesCommentsController@onUpload');
	$router -> post('delete-file', 'NoticesCommentsController@onDeleteFile');
	$router -> post('files', 'NoticesCommentsController@getFiles');
	$router -> post('reaction', 'NoticesCommentsController@onReaction');
});
