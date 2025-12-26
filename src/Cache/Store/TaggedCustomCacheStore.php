<?php

declare(strict_types=1);

namespace Maher\CoreTools\Cache\Store;

use Illuminate\Contracts\Cache\Store;

use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Maher\CoreTools\Cache\Drivers\CustomCacheTaggedItem;

class TaggedCustomCacheStore implements Store
{
    public $basePath;

    public function __construct($basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
    }

    protected function path($directory, $key)
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . md5($key) /* . '.cache' */;
    }

    protected function splitKey($key)
    {
        return str_contains($key, ':') ? explode(':', $key, 3) : ['default', $key];
    }

    protected function isExpired($file)
    {
        if (!File::exists($file)) return true;

        $content = @unserialize(File::get($file));
        if (!is_array($content) || !isset($content['expires_at'], $content['value'])) return true;

        if ($content['expires_at'] === 0) return false;

        return Carbon::now()->timestamp > $content['expires_at'];
    }

    /** ------------------ Automatic cleaning ------------------ **/
    protected function cleanDirectory($dirPath)
    {
        if (!File::exists($dirPath)) return;
        foreach (File::files($dirPath) as $file) {
            if ($this->isExpired($file)) {
                File::delete($file);
            }
        }
    }

    /** ------------------ Basic Store Methods ------------------ **/
    public function get($key)
    {
        [$dir, $realKey] = $this->splitKey($key);
        $dirPath = $this->basePath . DIRECTORY_SEPARATOR . $dir;
        $this->cleanDirectory($dirPath); // تنظيف تلقائي

        $file = $this->path($dir, $realKey);
        if ($this->isExpired($file)) {
            File::exists($file) && File::delete($file);
            return null;
        }

        $content = unserialize(File::get($file));
        return $content['value'];
    }

    public function put($key, $value, $seconds = 0)
    {
        [$dir, $realKey] = $this->splitKey($key);
        $dirPath = $this->basePath . DIRECTORY_SEPARATOR . $dir;
        if (!File::exists($dirPath)) File::makeDirectory($dirPath, 0755, true);

        $this->cleanDirectory($dirPath); // تنظيف تلقائي قبل الحفظ

        $expiresAt = $seconds > 0 ? Carbon::now()->addSeconds($seconds)->timestamp : 0;

        File::put($this->path($dir, $realKey), serialize([
            'value' => $value,
            'expires_at' => $expiresAt,
        ]));
    }

    public function forever($key, $value)
    {
        $this->put($key, $value, 0);
    }

    public function forget($key)
    {
        [$dir, $realKey] = $this->splitKey($key);
        $file = $this->path($dir, $realKey);
        return File::exists($file) ? File::delete($file) : false;
    }

    public function flush()
    {
        return File::deleteDirectory($this->basePath, true);
    }

    public function increment($key, $value = 1)
    {
        $current = $this->get($key) ?? 0;
        $this->put($key, $current + $value, 0);
        return $current + $value;
    }

    public function decrement($key, $value = 1)
    {
        return $this->increment($key, -$value);
    }

    public function getPrefix()
    {
        return '';
    }

    public function many(array $keys)
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key);
        }
        return $results;
    }

    public function putMany(array $values, $seconds)
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, $seconds);
        }
    }

    /** ------------------ Tags ------------------ **/
    public function tags($names)
    {
        return new CustomCacheTaggedItem($this, (array) $names);
    }
}
