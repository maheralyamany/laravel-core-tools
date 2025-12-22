<?php
namespace Maher\CoreTools;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Maher\CoreTools\Support\HelpersLoader;
use Maher\CoreTools\Security\Middleware\RequestSecurityMiddleware;
use Maher\CoreTools\Security\Events\SuspiciousRequestDetected;
use Maher\CoreTools\Security\Listeners\LogSuspiciousRequest;

class CoreToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/core-tools.php','core-tools');
    }

    public function boot(Router $router): void
    {

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

        Event::listen(SuspiciousRequestDetected::class, LogSuspiciousRequest::class);
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
}