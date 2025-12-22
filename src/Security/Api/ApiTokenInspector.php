<?php
namespace Maher\CoreTools\Security\Api;
use Illuminate\Http\Request;
class ApiTokenInspector {
    public function valid(Request $r): bool {
        $t=$r->bearerToken();
        return $t && strlen($t)>=32;
    }
}