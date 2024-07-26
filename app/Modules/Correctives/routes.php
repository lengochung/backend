<?php
$namespace = 'App\Modules\Correctives\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'correctives',
	'namespace' => $namespace
], function ($router) {
	$router -> post('list', 'CorrectivesController@getAllList');
	$router -> post('save', 'CorrectivesController@onSave');
	$router -> post('detail', 'CorrectivesController@getCorrectiveDetail');
	$router -> post('filter-column', 'CorrectivesController@getFilterColumn');
});
