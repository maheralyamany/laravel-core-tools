<?php

namespace Maher\CoreTools;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Cache;
use Maher\CoreTools\Support\HelpersLoader;
use Maher\CoreTools\Security\Middleware\RequestSecurityMiddleware;
use Illuminate\Support\Facades\Config;
use Maher\CoreTools\Cache\Store\TaggedCustomCacheStore;
use Maher\CoreTools\Support\CoreToolsConstants;
use Illuminate\Support\{Arr, Collection, Str};
use Maher\CoreTools\Support\{StringHelper, ArrayToPhpConverter, ArrayComparator, ArrayHelper, CollectionHelper};

class CoreToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->extendMacros();
        $this->mergeConfigFrom(__DIR__ . '/../config/core-tools.php', 'core-tools');
    }

    public function boot(Router $router): void
    {



        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/core-tools.php' => config_path('core-tools.php'),
            ], 'core-tools-config');
            $this->publishes([
                __DIR__ . '/../resources/views' => base_path('resources/views/vendor/core-tools'),
            ], 'views');
            $this->registerMigrations();

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'core-tools-migrations');
            //resources\views\errors\security.blade.php
        }
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'core-tools');
        $this->registerCacheStores();
        $this->registerLoggingChannel();
        HelpersLoader::load(__DIR__ . '/Helpers');

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
            return $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }
    protected function registerCacheStores()
    {
        $cache_key = CoreToolsConstants::TAGED_CACHE_KEY;
        $stores = Config::get('cache.stores');
        $cache_path = "";
        if (!isset($stores[$cache_key])) {
            $cache_path = storage_path("framework/cache/data/{$cache_key}");
            $cache_store = Config::get('core-tools.cache.taged_cache_store', [
                'driver' => $cache_key,
                'path' =>  $cache_path,
                'lock_path' =>  $cache_path,
                'tags' => true,
            ]);
            $stores[$cache_key] = $cache_store;
            Config::set("cache.stores.{$cache_key}",  $cache_store);
        }
        if (isset($stores[$cache_key]['path']) && !empty($stores[$cache_key]['path'])) {
            $cache_path = $stores[$cache_key]['path'];
        } else {
            $cache_path = storage_path("framework/cache/data/{$cache_key}");
        }
        Cache::extend($cache_key, function ($app) use ($cache_path) {
            return Cache::repository(new TaggedCustomCacheStore($cache_path));
        });
    }
    protected function registerLoggingChannel()
    {

        $channels = Config::get('logging.channels');
        $channel_key = CoreToolsConstants::SECURITY_LOGGING_CHANNEL_KEY;
        if (!isset($channels[$channel_key])) {
            $security_channel = Config::get('core-tools.security.channel', [
                'driver' => 'daily',
                'path' => storage_path('logs/security.log'),
                'level' => 'warning',
            ]);
            Config::set("logging.channels.{$channel_key}", $security_channel);
        }
    }
    /**
     * Extend Laravel macros.
     */
    protected function extendMacros(): void
    {
        Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            return CollectionHelper::collectionPaginate($this, $perPage, $total, $page, $pageName);
        });
        Collection::macro('mGroupBy', function ($groupBy, $forgetKey = true, $preserveKeys = false, bool $pluckEmpty = false) {
            return CollectionHelper::mGroupBy($this, $groupBy, $forgetKey, $preserveKeys, $pluckEmpty);
        });
        Str::macro('removeComments', fn($string) => StringHelper::removeComments($string));
        Str::macro('containsAny', fn($haystack, $needles, $ignoreCase = false) => StringHelper::containsAny($haystack, $needles, $ignoreCase));
        $this->extendArrayMacros();
    }
    protected function extendArrayMacros(): void
    {
        //toPhpString
        Arr::macro('toPhpString', fn(array $array, array $options = []) => ArrayToPhpConverter::toPhpString($array, $options));
        Arr::macro('toCompactPhpString', fn(array $array, array $options = []): string => ArrayToPhpConverter::toCompactPhpString($array, $options));
        Arr::macro('saveToFile', fn(array $array, string $filename, array $options = []) => ArrayToPhpConverter::saveToFile($array, $filename, $options));
        //arrayDiffAssoc
        Arr::macro('arrayDiffAssoc', fn(array $array1, array $array2, string $compare = 'both'): array => ArrayComparator::diff($array1, $array2, $compare));
        //merge
        Arr::macro('merge', fn(array $array1, array $array2): array => ArrayHelper::merge($array1, $array2));
        Arr::macro('toJsonArray', fn(array|object|null $data, int $depth = 512): string => ArrayHelper::toJsonArray($data, $depth));
        Arr::macro('toJson', fn(array|object|null $data, int $depth = 512): string => ArrayHelper::toJson($data, $depth));
        Arr::macro('filter', fn(array $array, ?callable $callback = null): array => ArrayHelper::filter($array, $callback));
        Arr::macro('count', fn(array $array, callable $callback): int => ArrayHelper::count($array, $callback));
        Arr::macro('chunkAssociative', fn(array $array, int $size = 0): array => ArrayHelper::chunkAssociative($array, $size));
        //mergeRecursive
        Arr::macro('mergeRecursive', fn(array $array1, array $array2, string $strategy = 'overwrite') => ArrayHelper::mergeRecursiveCustom($array1, $array2, $strategy));
        //mergeWithValidation
        Arr::macro('mergeWithValidation', fn(array $array1, array $array2, string $strategy = 'overwrite', $allowedTypes = null) => ArrayHelper::mergeWithValidation($array1, $array2, $strategy, $allowedTypes));
        //mergeAdvanced
        Arr::macro('mergeAdvanced', fn(array $array1, array $array2, array $options = []) => ArrayHelper::mergeAdvanced($array1, $array2, $options));
        //mergeMultiple
        Arr::macro('mergeMultiple', fn($strategy = 'overwrite', ...$arrays) => ArrayHelper::mergeMultiple($strategy, ...$arrays));
        Arr::macro('filterMapWithKeys', function (array $array, callable $callback, bool $array_values = false) {
            return ArrayHelper::filterMapWithKeys($array, $callback, $array_values);
        });
    }
}
