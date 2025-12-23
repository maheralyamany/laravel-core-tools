<?php
namespace Maher\CoreTools;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

use Maher\CoreTools\Support\HelpersLoader;
use Maher\CoreTools\Security\Middleware\RequestSecurityMiddleware;
use Illuminate\Support\Facades\Config;
class CoreToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/core-tools.php','core-tools');
    }

    public function boot(Router $router): void
    {

        $this->registerLoggingChannel();
        if (app()->runningInConsole()) {
            $this->registerMigrations();

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'core-tools-migrations');

          
        }
        $this->publishes([
            __DIR__.'/../config/core-tools.php' => config_path('core-tools.php'),
        ], 'core-tools-config');

        HelpersLoader::load(__DIR__.'/Helpers');

        $router->aliasMiddleware('core.security', RequestSecurityMiddleware::class);

        
    }
     /**
     * Register CoreTools's migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        if (CoreTools::shouldRunMigrations()) {
            return $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }
    protected function registerLoggingChannel()
    {
         $channels = Config::get('logging.channels');
         if (!isset($channels['security'])) {
            Config::set('logging.channels.security', [
                'driver' => 'daily',
                'path' => storage_path('logs/security.log'),
                'level' => 'warning',
            ]);
        }
    }
}