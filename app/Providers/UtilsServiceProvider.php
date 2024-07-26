<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class UtilsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        require_once(app_path() . '/Utils/Constants.php');
        require_once(app_path() . '/Utils/Lib.php');
        require_once(app_path() . '/Utils/Files.php');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
