<?php

namespace Maher\CoreTools\Security\Request;

use Illuminate\Http\Request;

class IpGuard
{
    public function isAllowed(Request $r): bool
    {
        return !in_array($r->ip(), config('core-tools.security.blocked_ips', []), true);
    }
}
