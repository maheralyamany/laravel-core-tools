<?php

namespace Maher\CoreTools\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Maher\CoreTools\Security\Request\IpGuard;
use Maher\CoreTools\Security\Request\RequestInspector;
use Maher\CoreTools\Security\RateLimit\AdvancedRateLimiter;
use Maher\CoreTools\Security\Api\ApiTokenInspector;

class RequestSecurityMiddleware
{
    public function handle(Request $r, Closure $n)
    {
        if (config('core-tools.modules.ip_guard') && !(new IpGuard)->isAllowed($r)) abort(403);
        if (config('core-tools.modules.rate_limit') && (new AdvancedRateLimiter)->tooManyAttempts($r)) abort(429);
        if (config('core-tools.modules.api_security') && !(new ApiTokenInspector)->valid($r)) abort(401);
        if ((new RequestInspector)->hasSuspiciousPayload($r)) abort(400);
        return $n($r);
    }
}
