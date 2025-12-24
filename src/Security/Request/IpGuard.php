<?php

namespace Maher\CoreTools\Security\Request;

use Illuminate\Http\Request;
use Maher\CoreTools\Security\Models\CoreBlockIp;

class IpGuard
{
    public function isAllowed(Request $r): bool
    {
        
        return !CoreBlockIp::isIpBlocked($r->ip());
    }
}
