<?php
$namespace = 'App\Modules\Notices\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'notices',
	'namespace' => $namespace
], function ($router) {
	$router -> post('list', 'NoticesController@getAllList');
	$router -> post('save', 'NoticesController@onSave');
	$router -> post('detail', 'NoticesController@getDetail');
	$router -> post('upload', 'NoticesController@onUpload');
	$router -> post('delete-file', 'NoticesController@onDeleteFile');
	$router -> post('files', 'NoticesController@getFiles');
    $router -> post('filter-column', 'NoticesController@getFilterColumn');
    $router -> post('export-pdf', 'NoticesController@exportPDF');
});
