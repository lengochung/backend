<?php
$namespace = 'App\Modules\Malfunctions\Controllers';
$router->group([
	'middleware' => [AUTH_API_GUARD, 'jwt.auth'],
	'prefix' => 'malfunctions',
	'namespace' => $namespace
], function ($router) {
    $router -> post('malfunctions-dropdown', 'MalfunctionsController@getMalfunctionsDropdown');
});
