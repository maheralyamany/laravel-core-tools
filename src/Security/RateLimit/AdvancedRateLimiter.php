<?php

namespace Maher\CoreTools\Security\RateLimit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class AdvancedRateLimiter
{
    public function tooManyAttempts(Request $r): bool
    {
        if (!config('core-tools.security.rate_limit.enabled')) return false;
        $key = 'core_rate_' . sha1($r->ip() . '|' . $r->path());
        $max = config('core-tools.security.rate_limit.max_requests', 100);
        $count = Cache::get($key, 0);
        if ($count >= $max) return true;
        Cache::put($key, $count + 1, now()->addMinute());
        return false;
    }
}
