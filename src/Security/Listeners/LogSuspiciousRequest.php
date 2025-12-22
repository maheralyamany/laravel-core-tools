<?php

namespace Maher\CoreTools\Security\Listeners;

use Illuminate\Support\Facades\Log;
use Maher\CoreTools\Security\Events\SuspiciousRequestDetected;

class LogSuspiciousRequest
{
  public function handle(SuspiciousRequestDetected $e): void
  {
    Log::warning('Suspicious request', ['ip' => $e->request->ip(), 'reason' => $e->reason]);
  }
}
