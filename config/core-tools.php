<?php
use Maher\CoreTools\Security\Models\CoreBlockIp;
return [
    'modules' => [
        'ip_guard' => true,
        'rate_limit' => true,
        'api_security' => true,
        'audit' => true,
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
        'channel' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'warning',
        ],
        'blocked_ips' => [
            'table_name'=>'core_block_ips',
            'connection'=>null,
            'model'=>CoreBlockIp::class,
        ],
        'rate_limit' => [
            'enabled' => true,
            'max_requests' => 100,
        ],
    ],
];
