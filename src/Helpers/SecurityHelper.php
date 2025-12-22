<?php
if (!function_exists('mask_string')) {
  function mask_string(string $v, int $n = 4): string
  {
    return substr($v, 0, $n) . str_repeat('*', max(0, strlen($v) - $n));
  }
}
