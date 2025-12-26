<?php
return [
    'modules' => [
        'ip_guard' => true,// if true check if ip address is blocked
        'rate_limit' => true,// if true check max requests per minute
        'api_security' => true,// if true check if Api request has token
       
    ],
    'cache' => [
        'taged_cache_store' => [
            'driver' => 'tagged_cache',
            'path' => storage_path('framework/cache/data/tagged_cache'),
            'lock_path' => storage_path('framework/cache/data/tagged_cache'),
            'tags' => true, // Enable cache tags support (optional)
        ],
    ],
    'security' => [
        'enabled' => false, // if true request security it will register CheckRouteExistsMiddleware ,RequestSecurityMiddleware  
        'channel' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'warning',
        ],
        'check_routes' => [
            'enabled' => false,
            // تجاهل بعض المسارات مثل assets أو api
            'ignored_prefixes' => [
                'api',
                'sanctum',
                'storage',
                /* '_debugbar', 'vendor' */
            ],
        ],
        'blocked_ips' => [
            'run_migrations' => false,
            'table_name' => 'core_block_ips',
            'connection' => null,
            'model' => 'Maher\CoreTools\Security\Models\CoreBlockIp',
        ],
        'rate_limit' => [
            'enabled' => true,
            'max_requests' => 100,
        ],
    ],
];
