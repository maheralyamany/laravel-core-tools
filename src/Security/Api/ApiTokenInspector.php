<?php
namespace Maher\CoreTools\Security\Api;
use Illuminate\Http\Request;
class ApiTokenInspector {
    public function valid(Request $request): bool {
      //  if(!\isApiRequest($request))
            
        $t=$request->bearerToken();
        return $t && strlen($t)>=32;
    }
}