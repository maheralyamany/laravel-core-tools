<?php

namespace Maher\CoreTools\Contracts;

interface AuditLogger
{
  public function log(string $a, array $c = []): void;
}
