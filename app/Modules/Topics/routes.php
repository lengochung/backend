<?php
$namespace = 'App\Modules\Topics\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'topics',
	'namespace' => $namespace
], function ($router) {
    $router -> post('list', 'TopicsController@getAllList');
	$router -> post('save', 'TopicsController@onSave');
	$router -> post('detail', 'TopicsController@getDetail');
    $router -> post('upload', 'TopicsController@onUpload');
	$router -> post('delete-file', 'TopicsController@onDeleteFile');
	$router -> post('filter-column', 'TopicsController@getFilterColumn');
});
