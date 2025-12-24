<?php

declare(strict_types=1);

namespace Maher\CoreTools\Security\Helpers;

use Illuminate\Support\Facades\Http;
use Maher\CoreTools\Security\Models\CoreBlockIp;

class SecurityHelper
{
  public static array $cloudflareConfig = [];
  protected static $cf_email;
  protected static $cf_key;

  protected static array $blockIpsTableInfo = [];

  public static function getBlockIpsTableInfo(): array
  {
    if (empty(static::$blockIpsTableInfo)) {
      static::$blockIpsTableInfo = config('core-tools.security.blocked_ips', [
        'table_name' => 'core_block_ips',
        'connection' => null,
        'model' => CoreBlockIp::class,
      ]);
    }
    return static::$blockIpsTableInfo;
  }
  public static function getBlockIpsTableName():string
  {
    return static::$blockIpsTableInfo['table_name'] ?? 'core_block_ips';
  }
  public static function getBlockIpsConnectionName():?string
  {
    return static::$blockIpsTableInfo['connection'] ?? null;
  }
  public static function getBlockIpsModelName():string
  {
    return static::$blockIpsTableInfo['model'] ?? CoreBlockIp::class;
  }
  public static function getCfConfig($key)
  {
    if (!isset(self::$cloudflareConfig[$key])) {
      self::$cloudflareConfig = env($key);
    }
    return self::$cloudflareConfig[$key] ?? null;
  }
  public static function getCfHeaders()
  {
    $cf_email = self::getCfConfig('CF_EMAIL');
    $cf_key = self::getCfConfig('CF_KEY');
    if (!empty($cf_email) && !empty($cf_key)) {
      return [
        'Content-Type' => 'application/json',
        'X-Auth-Email' => $cf_email,
        'X-Auth-Key' => $cf_key
      ];
    }
    return null;
  }
  public static  function blockIpFirewall($ip, $user_agent = "", $user_id = 0)
  {
    try {
      $cf_headers = self::getCfHeaders();
      if (!empty($cf_headers)) {
        $res = Http::withHeaders($cf_headers)->post('https://api.cloudflare.com/client/v4/user/firewall/access_rules/rules', [
          'paused' => false,
          'mode' => 'block',
          'configuration' => ['target' => 'ip', 'value' => $ip],
          'notes' => 'Banned on ' . date('Y-m-d H:i:s') . ' by ' . env('APP_NAME') . ' Firewall ' . env('APP_ENV')
        ])->json();
        if ($res['success'] == true) {
          return self::blockIp($ip, $user_agent, $user_id, $res['result']['id']);
        }
      }
    } catch (\Throwable $th) {
      //throw $th;
    }
    return self::blockIp($ip, $user_agent, $user_id);
  }
  public static function blockIp(string $ip, $user_agent = "", $user_id = 0, $state_id = 0)
  {
    return CoreBlockIp::createIfNotExists($ip, [
      'state_id' => $state_id,
      'user_agent' => $user_agent,
      'user_id' => $user_id,
      'status' => "block",
    ]);
  }

  public static function isIpBlocked($ip): bool
  {
    return CoreBlockIp::isIpBlocked($ip);
  }
  public static function unblock_ip(CoreBlockIp $blocked_ip)
  {
    $cf_headers = self::getCfHeaders();
    if (!empty($cf_headers)) {
      $res = Http::withHeaders($cf_headers)->delete('https://api.cloudflare.com/client/v4/user/firewall/access_rules/rules/' . $blocked_ip->state_id)->json();

      return $res['success'] == true;
    }
    return false;
  }
  public static function enable_under_attack_mode()
  {
    return  self::toggleUnderAttackMode("under_attack");
  }
  public static function toggleUnderAttackMode($mode)
  {
    $cf_headers = self::getCfHeaders();
    if (!empty($cf_headers)) {
      $cf_z = self::getCfConfig('CF_Z');
      $res = Http::withHeaders($cf_headers)->patch(
        "https://api.cloudflare.com/client/v4/zones/" . $cf_z . "/settings/security_level",
        ['value' => $mode]
      )->json();
      return $res['success'] == true;
    }
    return true;
  }
  public static function disable_under_attack_mode()
  {
    return  self::toggleUnderAttackMode("medium");
  }
}
