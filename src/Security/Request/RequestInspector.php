<?php

namespace Maher\CoreTools\Security\Request;

use Illuminate\Http\Request;

class RequestInspector
{
    public function hasSuspiciousPayload(Request $r): bool
    {
        return str_contains(json_encode($r->all()), '<script');
    }
}
