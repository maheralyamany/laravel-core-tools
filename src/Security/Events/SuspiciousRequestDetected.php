<?php

namespace Maher\CoreTools\Security\Events;

use Illuminate\Http\Request;

class SuspiciousRequestDetected
{
  public function __construct(public Request $request, public string $reason) {}
}
