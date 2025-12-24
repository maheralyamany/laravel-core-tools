<?php

namespace Maher\CoreTools\Security\Audit;

use Illuminate\Support\Facades\Log;
use Maher\CoreTools\Support\CoreToolsConstants;

class LogAuditLogger implements \Maher\CoreTools\Contracts\AuditLogger
{
  public static function channel(): \Psr\Log\LoggerInterface
  {
    return   Log::channel(CoreToolsConstants::SECURITY_LOGGING_CHANNEL_KEY);
  }
  public static function getValidMessage(?string $message): string
  {
    if (is_null($message))
      $message = 'Malicious URL detected';
    return  '[SECURITY] ' . $message;
  }
  public static function info(?string $message, array $context = []): void
  {
    static::channel()->info(static::getValidMessage($message), $context);
  }
  public static function error(?string $message, array $context = []): void
  {
    static::channel()->error(static::getValidMessage($message), $context);
  }
  public static function warning(?string $message, array $context = []): void
  {
    static::channel()->warning(static::getValidMessage($message), $context);
  }
  public static function notice(?string $message, array $context = []): void
  {
    static::channel()->notice(static::getValidMessage($message), $context);
  }
  public static function debug(?string $message, array $context = []): void
  {
    static::channel()->debug(static::getValidMessage($message), $context);
  }
}
