<?php

use Illuminate\Support\Str;

if (!function_exists('safe_slug')) {
  function safe_slug(string $v): string
  {
    return Str::slug($v);
  }
}
if (!function_exists('m_empty')) {
	function m_empty(mixed $value): bool
	{
		return !isset($value) || $value === null || empty($value);
	}
}

if (!function_exists('m_not_empty')) {
	function m_not_empty($value): bool
	{
		return !m_empty($value);
	}
}
if (!function_exists('nullOrEmpty')) {
	function nullOrEmpty($value): bool
	{
		return m_empty($value);
	}
}
if (!function_exists('getValidTitle')) {
	function getValidTitle($title): string|null
	{
		if (m_empty($title))
			return $title;
		$title = Str::singular(Str::studly($title));
		//$title = str_replace('_', ' ', $title);
		return $title;
	}
}


if (!function_exists('emptyOrZero')) {
	function emptyOrZero($value): bool
	{
		return m_empty($value) || is_int($value) && intval($value) === 0;
	}
}
if (!function_exists('toStringIds')) {
	function toStringIds($ids, $int_val = true)
	{
		$array = ($int_val) ? toArrayIds($ids) : toStringArrayIds($ids);
		return	implode(',', $array);
	}
}
if (!function_exists('toStringArrayIds')) {
	function toStringArrayIds($ids, $withZero = false)
	{
		if (m_empty($ids))
			return  [];
		if (is_array($ids)) {
			if (count($ids) == 0)
				return  [];
			return collect($ids)->map(fn($id) => strval($id))->toArray();
		}
		$id = parseInt($ids);
		return ($id > 0 || $withZero) ? [strval($id)] : [];
	}
}
if (!function_exists('strContainsAny')) {
	/**
	 * Determine if a given string contains any array values.
	 *
	 * @param  string  $haystack
	 * @param  iterable<string>  $needles
	 * @param  bool  $ignoreCase
	 */
	function strContainsAny($haystack, $needles, $ignoreCase = true): bool
	{
		foreach ($needles as $needle) {
			if (Str::contains($haystack, $needle, $ignoreCase)) {
				return true;
			}
		}

		return false;
	}
}
if (!function_exists('limit_str')) {
	function limit_str($str, $length)
	{
		if (empty($str)) {
			return $str;
		}
		$length = intval($length);
		$value = $str;
		if (Str::length($value) > $length && $length > 0) {
			$value = Str::limit($value, $length);
		}
		return $value;
	}
}
if (!function_exists('limit_string')) {
	function limit_string($str, $length)
	{
		if (empty($str)) {
			return $str;
		}
		$value = $str;
		if (Str::length($value) > $length) {
			$split = explode(' ', $value);
			$siz = sizeof($split);
			if ($siz <= 2) {
				$value = Str::limit($value, $length);
			} else {
				$value = $split[0] . ' ' . $split[$siz - 1];
				if (Str::length($value) > $length) {
					$value = limit_string($value, $length);
				}
			}
		}
		return $value;
	}
}
if (!function_exists('short_name')) {
	function short_name($str, $max_length, $keep_last = false)
	{
		if (empty($str)) {
			return $str;
		}
		$value = $str;
		if (Str::length($value) > $max_length) {
			$split = explode(' ', $value);
			$siz = sizeof($split);
			if ($siz <= 2) {
				$value = Str::limit($value, $max_length);
			} else {
				if (!$keep_last) {
					unset($split[$siz - 1]);
				} else {
					unset($split[$siz - 2]);
				}
				$value = implode(' ', $split);
				if (Str::length($value) > $max_length) {
					$value = short_name($value, $max_length, $keep_last);
				}
			}
		}
		return $value;
	}
}
if (!function_exists('snake_case')) {
	/**
	 * Convert a string to snake case.
	 *
	 * @param  string  $value
	 * @param  string  $delimiter
	 * @return string
	 */
	function snake_case($value, $delimiter = '_')
	{
		return Str::snake($value, $delimiter);
	}
}
if (!function_exists('mask_string')) {
  function mask_string(string $v, int $n = 4): string
  {
    return substr($v, 0, $n) . str_repeat('*', max(0, strlen($v) - $n));
  }
}