<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $modulesPath = app_path('Modules');
        
        if (File::exists($modulesPath)) {
            $modules = File::directories($modulesPath);
            
            foreach ($modules as $module) {
                $moduleName = basename($module);
                $routesPath = $module . '/Routes';
                
                // Load API routes
                if (File::exists($routesPath . '/api.php')) {
                    $this->loadRoutesFrom($routesPath . '/api.php');
                }
                
                // Load web routes
                if (File::exists($routesPath . '/web.php')) {
                    $this->loadRoutesFrom($routesPath . '/web.php');
                }
            }
        }
    }
}

