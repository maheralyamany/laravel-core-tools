<?php

namespace Maher\CoreTools\Security\Audit;

use Illuminate\Support\Facades\Log;
use Maher\CoreTools\Contracts\AuditLogger;

class LogAuditLogger implements AuditLogger
{
  public function log(string $a, array $c = []): void
  {
    Log::info('[SECURITY] ' . $a, $c);
  }
}
