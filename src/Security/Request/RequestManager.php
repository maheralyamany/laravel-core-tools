<?php

declare(strict_types=1);

namespace Maher\CoreTools\Security\Request;

use Maher\CoreTools\Core\Dynamic\DyDataStoreTrait;
use Illuminate\Foundation\Auth\User;
use Maher\CoreTools\Security\Helpers\SystemInfoHelper;
use Illuminate\Http\Request;

class RequestManager
{
  use DyDataStoreTrait;
  /**
   * request
   * @var \Illuminate\Http\Request
   */
  protected $request;

  public function __construct(\Illuminate\Http\Request $request = null)
  {
    $this->request = $request;
  }
  public  function getRequest(): Request
  {
    if (\is_null($this->request)) {
      $this->request = request();
    }
    return $this->request;
  }
  /**
   * Set request
   *
   * @param  \Illuminate\Http\Request  $request  request
   *
   * @return  self
   */
  public function setRequest(\Illuminate\Http\Request $request)
  {
    $this->clearDyData();
    $this->request = $request;
    return $this;
  }
  public function getBaseUrl(): string
  {
    return $this->getDyData('BaseUrl', fn() => url('/'));
  }
  public function getUserAgent(): ?string
  {
    return $this->getDyData('RequestUserAgent', fn() => $this->getRequest()->userAgent());
  }
  public function isLivewireRequest($request = null)
  {
    $request = $request ?? $this->getRequest();
    return $request->is('*/livewire/*') || $request->hasHeader('X-Livewire');
  }
  public function getQueryString(): ?string
  {
    //$agent_name = $request->header('User-Agent');
    return $this->getDyData('RequestQueryString', fn() => $this->getRequest()->getQueryString());
  }
  public function getFullUrl(): string
  {
    return $this->getDyData('FullUrl', fn() => $this->getRequest()->fullUrl());
  }
  public function getUser(): User|null
  {

    return $this->getDyData('RequestUser', function () {
      return  $this->getRequest()->user() ?? auth()->user();
    });
  }
  public function isGuest(): bool
  {
    return $this->getDyData('IsGuestUser', function () {
      return  auth()->guest();
    });
  }
  public function getUserId(): int|null
  {
    return $this->getDyData('RequestUserId', function () {
      $user = $this->getUser();
      return $user?->id ?? null;
    });
  }
  public  function get_device()
  {
    return $this->getDyData('device', fn() => SystemInfoHelper::get_device());
  }
  public  function get_browsers()
  {
    return $this->getDyData('browsers', fn() =>  SystemInfoHelper::get_browsers());
  }
  public  function get_os()
  {
    return $this->getDyData('get_os', fn() =>  SystemInfoHelper::get_os());
  }
  public  function getIp(): string
  {
    return  $this->getDyData('IpAddress', function () {
      if (isset($_SERVER["HTTP_CF_CONNECTING_IP"]))
        return $_SERVER["HTTP_CF_CONNECTING_IP"];
      else if (isset($_SERVER['REMOTE_ADDR']))
        return $_SERVER['REMOTE_ADDR'];
      else if (isset($_SERVER['HTTP_CLIENT_IP']))
        return $_SERVER['HTTP_CLIENT_IP'];
      else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
      else if (isset($_SERVER['HTTP_X_FORWARDED']))
        return $_SERVER['HTTP_X_FORWARDED'];
      else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
        return $_SERVER['HTTP_FORWARDED_FOR'];
      else if (isset($_SERVER['HTTP_FORWARDED']))
        return $_SERVER['HTTP_FORWARDED'];
      else if (isset($_SERVER['REMOTE_ADDR']))
        return $_SERVER['REMOTE_ADDR'];
      else if (($request = $this->getRequest()) != null)
        return $request->getClientIp();
      else
        return 'UNKNOWN';
    });
  }
  public function maybeBase64($s)
  {
    // very simple heuristic: length multiple of 4 and only base64 chars
    $s = trim($s);
    if (strlen($s) % 4 !== 0) return false;
    return (bool) preg_match('/^[A-Za-z0-9\/\r\n+]*={0,2}$/', $s);
  }
  public function getLocationFromIp($ip)
  {

    $result = $this->getDySubData('LocationFromIp', $ip, fn() => SystemInfoHelper::getLocationFromIp($ip));

    return $result;
  }
  public function isLocalHost(): bool
  {
    $url = $this->getBaseUrl();
    return str_contains($url, 'localhost') || str_contains($url, '127.0.0.1');
  }
  public  function getValidUrl(&$url)
  {
    if (empty($url))
      $url = $this->getFullUrl();
    return $url;
  }
}
