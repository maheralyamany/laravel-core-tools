<?php

namespace Maher\CoreTools\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Maher\CoreTools\Security\Request\IpGuard;
use Maher\CoreTools\Security\Request\RequestInspector;
use Maher\CoreTools\Security\RateLimit\AdvancedRateLimiter;
use Maher\CoreTools\Security\Api\ApiTokenInspector;
use Maher\CoreTools\Security\Middleware\Traits\SecurityMiddlewareTrait;
use Maher\CoreTools\Security\Request\RequestManager;

class RequestSecurityMiddleware
{
    use SecurityMiddlewareTrait;
    public function handle(Request $request, Closure $next)
    {
        $hasMaliciousRequest = false;
        try {
            if (str_contains($request->path(), '.github')) {
                $hasMaliciousRequest = true;
                return $this->errorResponse($request, 404);
            }
            if (config('core-tools.modules.ip_guard') && !(new IpGuard)->isAllowed($request)) {
                $hasMaliciousRequest = true;
                return $this->errorResponse($request, 403, "Bad Request: Blocked Ip detected.");
            }

            if (config('core-tools.modules.rate_limit') && (new AdvancedRateLimiter)->tooManyAttempts($request)) {
                return $this->errorResponse($request, 429, "Bad Request: Too Many Attempts activity detected.");
            }
            if (config('core-tools.modules.api_security') && !(new ApiTokenInspector)->valid($request)) {
                return $this->errorResponse($request, 401);
            }
            $manager = new RequestManager($request);
            if ((new RequestInspector($manager))->checkMaliciousRequest()) {
                $hasMaliciousRequest = true;
                return $this->errorResponse($request);
            }
        } catch (\Exception $th) {
            report($th);
            if ($hasMaliciousRequest)
                abort(400, 'Bad Request: Suspicious activity detected.');

            //throw $th;
        }
        return $next($request);
    }
}
