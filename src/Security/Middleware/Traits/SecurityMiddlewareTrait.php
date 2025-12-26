<?php

declare(strict_types=1);

namespace Maher\CoreTools\Security\Middleware\Traits;

use Closure;
use Illuminate\Http\Request;

trait SecurityMiddlewareTrait
{
  protected function errorResponse(Request $request, int $code = 403, $msg = null, $title = null)
  {
    $msg = $msg ?? "Bad Request: Suspicious activity detected.";
    try {
      $data = ['code' => $code, 'message' => $msg];
      // Optionally: return custom error
      if (isAjaxRequest($request)) {
        return response()->json($data, $code);
      }

      if (!empty($title))
        $data['title'] = $title;
      return response()->view('core-tools-views::errors.security', $data);
      // Option: block with 400 or 403
      //abort(400, 'Bad Request: Suspicious activity detected.');
    } catch (\Exception $th) {
      //throw $th;
      abort($code, $msg);
    }
  }
}
