
<h1 align="center">Laravel Core Tools</h1>
<p align="center">
    <img src="https://img.shields.io/packagist/v/maheralyamany/laravel-core-tools" alt="Latest Version">
    <img src="https://img.shields.io/packagist/dt/maheralyamany/laravel-core-tools" alt="Total Downloads">
    <img src="https://img.shields.io/packagist/l/maheralyamany/laravel-core-tools" alt="License">
    <img src="https://img.shields.io/github/stars/maheralyamany/laravel-core-tools" alt="Stars">
</p>
<p align="center">
Enterprise-ready core helpers & security foundation for Laravel applications
</p>
laravel-core-tools is a professional Laravel package that provides a core foundation layer for modern applications, including:

Reusable helpers

Request & API security

Rate limiting

Unified security middleware


Audit trail

Modular and extensible architecture

Designed for SaaS platforms, APIs, government systems, and large-scale Laravel projects.

✨ Why Laravel Core Tools?

✔ Reduce duplicated logic across projects
✔ Centralize and standardize security rules
✔ Clean separation of concerns
✔ Octane-safe (stateless design)
✔ Built for long-term scalability

This is not just a helper package — it is a Core Security Layer.

🚀 Features
🔐 Security

IP Guard (allow / block)

Advanced rate limiting

API token inspection (Bearer)

Suspicious payload detection (basic XSS checks)

Unified security middleware





🧰 Helpers

String helpers

Security helpers

Automatic helper loading

🧩 Modular Architecture

Enable / disable features via config

Use only what you need

🧪 Testing Ready

Pest tests included

Clean, testable architecture

📦 Installation

```bash
composer require maheralyamany/laravel-core-tools
```

Publish the configuration file:
```bash
php artisan vendor:publish --tag=core-tools-config
```
⚙️ Configuration
```php
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


```
🛡️ Security Middleware

The middleware is registered automatically:
```php
\Maher\CoreTools\Security\Middleware\RequestSecurityMiddleware::class;
```


Includes:

IP validation

Rate limiting

API token validation

Payload inspection



Easy integration with:

Logs

Notifications

SIEM systems

External security services

📋 Audit Trail

A clean contract for logging security actions:
```php
AuditLogger::log('API_TOKEN_REJECTED', [
    'ip' => request()->ip(),
]);
```

You can replace the default logger with:

Database storage

Queues

External APIs

⚡ Laravel Octane Support

Stateless services

No shared memory state

Safe for Swoole & RoadRunner

🏗️ Package Structure
src/
 ├── Cache/
     ├── Drivers
 │   └── Store
 ├── Core/
     ├── Descriptor
     ├── Dynamic
 │   └── Generateors
 ├── Helpers/
 ├── Security/
 │   ├── Middleware
 │   ├── Request
 │   ├── RateLimit
 │   ├── Api
 │   ├── Models
 │   ├── Events
 │   ├── Listeners
 │   └── Audit
 ├── Contracts/
 └── Support/

🧪 Running Tests
./vendor/bin/pest

🎯 Ideal Use Cases

SaaS platforms

REST / GraphQL APIs

Government & enterprise systems

Multi-tenant applications

Large Laravel codebases

📌 Requirements

PHP 8.1+

Laravel 10 / 11 / 12 or higher

🛣️ Roadmap

Geo-IP blocking

Threat scoring system

Security metrics dashboard

Policy integration

AI-based anomaly detection

📄 License

MIT License

👤 Author

Maher
Senior Full-Stack Developer
Laravel • Security • SaaS Architecture