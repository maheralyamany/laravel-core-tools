<?php

use Illuminate\Support\Str;

if (!function_exists('safe_slug')) {
  function safe_slug(string $v): string
  {
    return Str::slug($v);
  }
}
