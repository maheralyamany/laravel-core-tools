<?php
declare(strict_types=1);
namespace Maher\CoreTools\Security\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maher\CoreTools\Security\Middleware\Traits\SecurityMiddlewareTrait;
class CheckRouteExistsMiddleware
{
  use SecurityMiddlewareTrait;
  /**
   * Handle an incoming request.
   */
  public function handle(Request $request, Closure $next)
  {
    $ignoredPrefixes = config('core-tools.security.check_routes.ignored_prefixes', [
      'api',
      'sanctum',
      'storage'
    ]);
    foreach ($ignoredPrefixes as $prefix) {
      if ($request->is($prefix . '/*') || $request->is($prefix)) {
        return $next($request);
      }
    }
    // تحقق إذا كان أي Route يطابق الطلب الحالي
    $matched = false;
    foreach ($this->getRoutes() as $route) {
      if ($route->matches($request)) {
        $matched = true;
        break;
      }
    }
    if (!$matched) {
      return $this->errorResponse($request, 404, "Page not found."); // ارجع خطأ 404
    }
    return $next($request);
  }
  /**
   * Summary of getRoutes
   * @return \Illuminate\Routing\Route[]
   */
  protected function getRoutes()
  {
    return Route::getRoutes()->getRoutes();
  }
}
