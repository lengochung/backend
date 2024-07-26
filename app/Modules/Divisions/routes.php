<?php
$namespace = 'App\Modules\Divisions\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'divisions',
	'namespace' => $namespace
], function ($router) {
	$router -> post('list', 'DivisionsController@getAllList');
	$router -> post('detail', 'DivisionsController@getDetail');
	$router -> post('save', 'DivisionsController@onSave');
	$router -> post('delete', 'DivisionsController@onDelete');
	$router -> post('status-list', 'DivisionsController@getDivisionsByPageNo');
    $router -> post('filter-column', 'DivisionsController@getFilterColumn');
    $router -> post('filter-search', 'DivisionsController@getListFilterSearch');
	$router -> post('divisions-dropdown', 'DivisionsController@getDivisionsDropdown');
});
