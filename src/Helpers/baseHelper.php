<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

if (!function_exists('taggedCache')) {
	function taggedCache(): Illuminate\Cache\Repository/* | \Illuminate\Contracts\Cache\Repository|\App\Cache\Store\TaggedCustomCacheStore */
	{ //CUSTOM_CASHE_KEY
		return \Illuminate\Support\Facades\Cache::store(\Maher\CoreTools\Support\CoreToolsConstants::TAGED_CACHE_KEY);
	}
}



if (!function_exists('parseBoolean')) {
	/**
	 * parse value to Boolean
	 *
	 * @param bool $default
	 * @return bool
	 */
	function parseBoolean(mixed $value, $default = false)
	{
		return match ($value) {
			'false', '0', 'FALSE', 0, false => false,
			'true', '1', 'TRUE', 1, true => true,
			default => $default,
		};
	}
}



if (!function_exists('autoCast')) {
	function autoCast($value, $default = null)
	{
		try {
			if (m_empty($value)) {
				return $default == null ? $value : value($default);
			}

			if (is_string($value)) {
				$trimmed = trim($value);
				// رقم صحيح أو عشري
				if (is_numeric($trimmed)) {
					return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
				}

				// boolean
				$lower = strtolower($trimmed);
				if (in_array($lower, [
					'true',
					'false',
					'1',
					'0',
				], true)) {
					return in_array($lower, [
						'true',
						'1',
					], true);
				}

				// JSON
				if (isJsonValue($trimmed)) {
					$decoded = json_decode($trimmed, true);
					if (json_last_error() === JSON_ERROR_NONE) {
						return $decoded;
					} else {
						return value($default ?? []);
					}
				}
			}
		} catch (Throwable $throwable) {
			//throw $th;
		}

		return $value;
	}
}

if (!function_exists('isJsonValue')) {
	function isJsonValue($string): bool
	{
		try {
			if (!is_string($string)) {
				return false;
			}
			$string = trim($string);
			return substr($string, 0, 1) === '{' || substr($string, 0, 1) === '[';
		} catch (Exception $exception) {
			//throw $th;
		}
		return false;
	}
}



if (!function_exists('parseInt')) {
	/**
	 * parse value to integer
	 *
	 * @param mixed $value
	 * @param callable|int|Closure $def_val
	 */
	function parseInt($value, $def_val = 0): int
	{
		try {
			if (is_null($value) || empty($value)) {
				return intval(def_value($def_val) ?? 0);
			} elseif (is_int($value)) {
				return $value;
			} elseif (is_array($value)) {
				$value = !empty($value)  ? intval(toArrayIds($value)[0] ?? 0) : 0;
			} else {
				$value = intval($value);
			}

			if ($value === 0) {
				$value = intval(def_value($def_val) ?? 0);
			}

			return $value;
		} catch (Throwable $throwable) {
		
			return intval(def_value($def_val) ?? 0);
		}
	}
}

if (!function_exists('def_value')) {
	/**
	 * Return the default value of the given value.
	 *
	 * @param  mixed  $value
	 * @param  mixed  ...$args
	 * @return mixed
	 */
	function def_value($value, ...$args)
	{
		if (is_null($value)) {
			return $value;
		}

		return $value instanceof Closure ? $value(...$args) : $value;
	}
}

if (!function_exists('parseFloat')) {
	function parseFloat($value)
	{
		try {
			return is_null($value) || empty($value) ? 0 : floatval($value);
		} catch (Throwable $throwable) {
		
			return $value;
		}
	}
}



if (!function_exists('hex_to_rgb')) {
	function hex_to_rgb($hex)
	{
		$result = preg_match('/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i', $hex, $matches);
		$data = $result ? hexdec($matches[1]) . ', ' . hexdec($matches[2]) . ', ' . hexdec($matches[3]) : null;
		return $data;
	}
}

if (!function_exists('format_biginteger')) {
	function format_biginteger($value)
	{
		$suffixes = [
			"1t+" => 1000000000000,
			"B+" => 1000000000,
			"M+" => 1000000,
			"K+" => 1000,
		];
		foreach ($suffixes as $suffix => $factor) {
			if ($value >= $factor) {
				$div = $value / $factor;
				$formatted_value = decimal_format($div, 1) . $suffix;
				break;
			}
		}

		if (!isset($formatted_value)) {
			$formatted_value = $value;
		}

		return $formatted_value;
	}
}

if (!function_exists('hex2rgb')) {
	function hex2rgb($colour)
	{
		if ($colour[0] == '#') {
			$colour = substr($colour, 1);
		}

		if (strlen($colour) === 6) {
			list($r, $g, $b,) = array($colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5]);
		} elseif (strlen($colour) === 3) {
			list($r, $g, $b,) = array($colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2]);
		} else {
			return false;
		}

		$r = hexdec($r);
		$g = hexdec($g);
		$b = hexdec($b);
		return array(
			'red' => $r,
			'green' => $g,
			'blue' => $b,
		);
	}
}
if (!function_exists('getDecimalPlaces')) {
	function getDecimalPlaces($value)
	{
		$value = floatval($value);
		// first we get how many decimal places the small number has
		// this code was gotten on another StackOverflow answer
		$current = $value - floor($value);
		for ($decimals = 0; ceil($current); $decimals++) {
			$current = ($value * pow(10, $decimals + 1)) - floor($value * pow(10, $decimals + 1));
		}
		return $decimals;
	}
}
if (!function_exists('decimal_format')) {
	function decimal_format(
		$number,
		int $decimals = 0,
		string|null $decimal_separator = ".",
		string|null $thousands_separator = ","
	) {
		try {
			if (is_null($number))
				return 0;
			$number = floatval($number);
			return number_format($number, $decimals, $decimal_separator, $thousands_separator);
		} catch (\Throwable $th) {
			
			return $number;
		}
	}
}

if (!function_exists('auto_decimal_format')) {
	function auto_decimal_format($number, $max_decimals = 4)
	{
		try {
			//$broken_number = explode('.', $number);
			$number = floatval($number);
			if (str_contains(strval($number), '.')) {
				$decimal_count = getDecimalPlaces($number);
				if ($decimal_count > $max_decimals) {
					$decimal_count = $max_decimals;
				}
				return number_format($number, $decimal_count);
			}
			return number_format($number);
		} catch (\Throwable $th) {
			report($th);

			return $number;
		}
	}
}
if (!function_exists('formatValue')) {
	function formatValue($value, $type)
	{
		if (m_empty($value) || \in_array($type, ["integer", "int", "string", "boolean"]))
			return $value;
		try {
			switch ($type) {
				case "numeric":
				case "double":
				case "float":
					return auto_decimal_format($value);
				case "integer":
				case "int":
					return $value;
				case "string":
					return $value;
				case "boolean":
					return $value;
				case "datetime":
					return format_date($value);
					// no break
				case "date":
					return format_date($value);
				case "time":
					return format_time($value);
			}
		} catch (\Throwable $th) {
			report($th);
		}
		return $value;
	}
}
if (!function_exists('gettype_fromstring')) {
	function gettype_fromstring($string, $def_type = 'string')
	{
		if (m_empty($string))
			return $def_type;
		if (is_bool($string))
			return 'boolean';
		if (is_int($string))
			return 'integer';
		if (is_numeric($string))
			return 'numeric';
		if (\is_array($string))
			return 'array';
		if (is_object($string))
			return 'object';
		if (validateDate($string))
			return 'datetime';
		//  (c) José Moreira - Microdual (www.microdual.com)
		return gettype(getcorrectvariable($string));
	}
}
if (!function_exists('getcorrectvariable')) {
	function getcorrectvariable($string)
	{
		if (gettype($string) === 'array') {
			return (array)$string;
		}
		$string = trim($string);
		if ($string === '0') { // we must check this before empty because zero is empty
			return 0;
		}
		if (empty($string)) {
			return '';
		}
		if ($string === 'null') {
			return null;
		}
		if ($string === 'undefined') {
			return null;
		}
		if ($string === '1') {
			return 1;
		}
		if (!preg_match('/[^0-9.]+/', $string)) {
			if (preg_match('/[.]+/', $string)) {
				return (float)$string;
			} else {
				return (int)$string;
			}
		}
		if ($string == 'true') {
			return true;
		}
		if ($string == 'false') {
			return false;
		}
		return (string)$string;
	}
}
if (!function_exists('isNullOrEmpty')) {
	function isNullOrEmpty($value): bool
	{
		return m_empty($value);
	}
}

if (!function_exists('isEmptyOrZero')) {
	function isEmptyOrZero($value): bool
	{
		return m_empty($value) || is_int($value) && intval($value) === 0;
	}
}


if (! function_exists('normalizeBackslashes')) {
	function normalizeBackslashes($value)
	{
		if (\is_string($value)) {
			// أولاً: استبدال 3 backslashes أو أكثر بواحدة
			$string = preg_replace('/\\\{3,}/', '\\', $value);
			// ثانياً: التأكد من عدم وجود double backslashes غير مرغوب فيها
			$string = str_replace('\\\\', '\\', $string);
			return $string;
		}
		if (\is_array($value)) {
			return Arr::mapWithKeys($value, fn($v, $k) => [$k => normalizeBackslashes($v)]);
			
		}
		if (is_object($value))
			return  normalizeBackslashes(get_class($value));
		return $value;
	}
}