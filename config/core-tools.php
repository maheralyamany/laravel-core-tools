<?php
return [
    'modules' => [
        'ip_guard' => true,
        'rate_limit' => true,
        'api_security' => true,
        'audit' => true,
    ],
    'security' => [
        'blocked_ips' => [],
        'rate_limit' => [
            'enabled' => true,
            'max_requests' => 100,
        ],
    ],
];