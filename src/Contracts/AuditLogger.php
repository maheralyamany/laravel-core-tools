<?php

namespace Maher\CoreTools\Contracts;

interface AuditLogger
{
   public static function channel(): \Psr\Log\LoggerInterface;
  public static function info(?string $message, array $context = []): void;
  public static function error(?string $message, array $context = []): void;
  public static function warning(?string $message, array $context = []): void;
  public static function notice(?string $message, array $context = []): void;
  public static function debug(?string $message, array $context = []): void;
}
