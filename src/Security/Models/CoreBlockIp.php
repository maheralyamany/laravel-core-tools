<?php

declare(strict_types=1);

namespace Maher\CoreTools\Security\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Maher\CoreTools\Security\Helpers\SecurityHelper;

class CoreBlockIp extends Model
{





  /**
   *  Attributes that should be mass-assignable.
   * 
   * @var array
   */
  protected $fillable = ['ip', 'user_id', 'user_agent', 'status', 'note', 'state_id', 'created_at', 'updated_at'];

  /**
   *  Attributes that should be string null.
   * 
   * @var array
   */
  protected $forcedNullStrings = ['user_agent', 'note', 'state_id', 'created_at', 'updated_at'];

  /**
   * The attributes that should be casted to native types.
   * 
   * @var array
   */
  protected $casts = ['user_id' => 'int', 'status' => 'boolean', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

  /**
   * The attributes that should be date  types.
   * 
   * @var array
   */
  protected $dates = ['created_at', 'updated_at'];

  /**
   * The storage format of the model's date columns.
   * 
   * @var string
   */
  protected $dateFormat = 'Y-m-d H:i:s';
  /**
   * Get the table associated with the model.
   *
   * @return string
   */
  public function getTable(): string
  {

    return  SecurityHelper::getBlockIpsTableName();
  }
  /**
   * Get the current connection name for the model.
   *
   * @return string|null
   */
  public function getConnectionName()
  {

    return SecurityHelper::getBlockIpsConnectionName();
  }

  public static function createIfNotExists(string $ip, array $attributes = []): bool
  {
    $user_id = $attributes['user_id'] ?? 0;
    static::clearCacheBlocked($ip);
    if (!static::query()->where('ip', $ip)->exists() || $user_id > 0) {
      $item = static::create([
        'ip' => $ip,
        'user_id' => $user_id,
        'state_id' => $attributes['state_id']  ?? null,
        'user_agent' => Str::limit($attributes['user_agent']  ?? '', 255),
        'status' => $attributes['status']  ?? 'block',
        'note' => $attributes['note']  ?? '',
        'created_at' => $attributes['created_at']  ?? now(),
        'updated_at' => $attributes['updated_at']  ?? now(),
      ]);
      return $item != null;
    }
    return true;
  }
  public static function findByIp($ip): self|null
  {
    return static::query()->where('ip', $ip)->first();
  }
  public static function isIpBlocked($ip): bool
  {
    $exists = Cache::remember("blocked_ip_" . $ip, 3600, function () use ($ip) {
      return static::query()->where('ip', $ip)->exists();
    });
    //$exists = static::query()->where('ip', $ip)->count('ip') > 0;

    return (bool)$exists;
  }
  public static function clearCacheBlocked($ip)
  {
    Cache::forget("blocked_ip_" . $ip);
  }
}
