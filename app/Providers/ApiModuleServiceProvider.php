<?php
namespace App\Providers;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use File;

class ApiModuleServiceProvider extends \Illuminate\Support\ServiceProvider{
    public function boot(){
        $modulePath = $this -> _modulePath();
        if (!$modulePath) {
            return;
        }
        Route::group([
            'prefix'=> env('API_PATH_PREFIX'),
            'middleware' => 'auth.request',
        ], function (Router $router) use ($modulePath) {
            $modules = array_map('basename', File::directories($modulePath));
            foreach ($modules as $module) {
                $this -> _registerRoutes($modulePath.$module, $router);
            }
        });
    }
    public function register() {}
    /**
    * Module path
    * @return string
    */
    private function _modulePath(){
        $dir = '/../Modules/';
        if(!is_dir(__DIR__ . $dir)) {
            return;
        }
        return __DIR__ . $dir;
    }
   /**
    * Register the "routes" for the application.
    * @return void
    */
    private function _registerRoutes($modulePath, $router) {
        if(File::exists($modulePath.DIRECTORY_SEPARATOR.'routes.php')) {
            require_once $modulePath.DIRECTORY_SEPARATOR.'routes.php';
        }
    }
}