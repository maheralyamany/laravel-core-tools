<?php

use Illuminate\Support\Str;

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
if (!function_exists('getPaymentValidTitle')) {
	function getPaymentValidTitle($gateway): string|null
	{
		if (m_empty($gateway))
			return '';
		$additional_data = $gateway['additional_data'] != null ? json_decode($gateway['additional_data']) : [];
		if ($additional_data != null) {
			if (m_empty($additional_data->gateway_title))
				return getValidTitle($gateway->key_name);
			return $additional_data->gateway_title;
		}
		return '';
	}
}

if (!function_exists('emptyOrZero')) {
	function emptyOrZero($value): bool
	{
		return m_empty($value) || is_int($value) && intval($value) === 0;
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

if (!function_exists('getValueByKey')) {
	function getValueByKey(array $array, string $key, $default = null)
	{
		$keys = explode('.', $key);
		$value = $array;
		foreach ($keys as $k) {
			// فك JSON مرة واحدة فقط إذا كانت string
			if (is_string($value) && isJsonValue($value)) {
				$decoded = json_decode($value, true);
				if (json_last_error() === JSON_ERROR_NONE) {
					$value = $decoded;
				}
			}

			// الوصول إلى المصفوفة أو JSON بعد فكها
			if (is_array($value)) {
				if (array_key_exists($k, $value)) {
					$value = $value[$k];
				} elseif (is_numeric($k) && array_key_exists((int) $k, $value)) {
					$value = $value[(int) $k];
				} else {
					return value($default);
				}
			} else {
				return value($default);
			}
		}

		return autoCast($value, $default);
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

if (!function_exists('jsonToArray')) {
	function jsonToArray($string, $default = [])
	{
		if (m_empty($string)) {
			return value($default);
		}

		if (is_array($string)) {
			return $string;
		}

		if (!is_string($string)) {
			return value($default);
		}

		$trimmed = trim($string);
		if (isJsonValue($trimmed)) {
			$decoded = json_decode($trimmed, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				return $decoded;
			}
		}

		return value($default);
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
				$value = $value !== [] ? intval(toArrayIds($value)[0] ?? 0) : 0;
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

if (!function_exists('toArrayIds')) {

	/**
	 * convert value to  array
	 * @param mixed $value
	 * @param (callable(): array)|null $notArraycallback
	 * @return array
	 */
	function toArrayIds($value, ?callable $notArraycallback = null): array
	{
		$value = toArray($value, $notArraycallback);
		if ($value === []) {
			return [];
		}

		return collect($value)->filter(function ($id): bool {
			if (m_empty($id)) {
				return false;
			}

			if (is_numeric($id)) {
				return parseInt($id) !== 0;
			}

			return true;
		})->toArray();
	}
}

if (!function_exists('toArray')) {
	/**
	 * convert value to  array
	 * @param mixed $value
	 * @param (callable(): array)|null $notArraycallback
	 * @return array
	 */
	function toArray($value, ?callable $notArraycallback = null): array
	{
		try {
			if (m_empty($value)) {
				return [];
			}
			if (is_array($value)) {
				if (array_empty($value)) {
					return [];
				}
				return $value;
			}
			if (is_numeric($value)) {
				return [$value];
			}
			if (is_string($value)) {
				if (Str::isJson($value)) {
					return json_decode((string) $value, true);
				}
				if (Str::contains(trim($value), ","))
					return explode(',', trim($value));
			}
			if ($notArraycallback != null && is_callable($notArraycallback)) {
				return $notArraycallback();
			}
		} catch (\Throwable $th) {
		
			//throw $th;
		}
		return [$value];
	}
}
if (!function_exists('isArray')) {
	function isArray($value): bool
	{
		if (m_empty($value)) {
			return false;
		}
		if (is_array($value)) {
			return true;
		}
		if (is_string($value)) {
			if (Str::isJson($value)) {
				return true;
			}
		}
		return false;
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

if (!function_exists('strBase64DecodeAll')) {
	function strBase64DecodeAll(...$strings)
	{
		$results = [];
		foreach ($strings as $string) {
			$results[$string] = base64_decode($string);
		}
		
		return $results;
	}
}

