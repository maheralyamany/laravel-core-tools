<?php

declare(strict_types=1);


use Illuminate\Support\Collection;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;
use Maher\CoreTools\Support\ArrayHelper;
use Maher\CoreTools\Support\MCollection;

if (!function_exists('prepend_arr')) {
	function prepend_arr(&$array, $value, $key = null)
	{
		if (func_num_args() === 2) {
			array_unshift($array, $value);
		} else {
			$array = [
				$key => $value,
			] + $array;
		}

		return $array;
	}
}
if (! function_exists('arrx')) {
	/**
	 * Create a collection from the given value.
	 *
	 * @template TKey of array-key
	 * @template TValue
	 *
	 * @param  array<TKey, TValue>|null  $value
	 * @return \Maher\CoreTools\Support\MArray<TKey, TValue>
	 */
	function arrx($value = [])
	{
		return new \Maher\CoreTools\Support\MArray($value);
	}
}
if (!function_exists('arr')) {
	/**
	 * Get a new Arr object from the given array.
	 */
	function arr(): Arr
	{

		return new class extends Arr
		{
			/* public function __call($method, $parameters)
            			{
            				return Arr::$method(...$parameters);
            			} */
		};
	}
}

if (!function_exists('sprintfx')) {
	function sprintfx(mixed ...$values): string
	{
		$format = "";
		foreach ($values as $value) {
			if (is_array($value)) {
				$format .= sprintfx($value);
			} else {
				$format .= $value;
			}
		}

		return $format;
	}
}

if (!function_exists('strx')) {
	/**
	 * Get a new Str object from the given string.
	 *
	 */
	function strx(): \Illuminate\Support\Str
	{
		return new class extends Illuminate\Support\Str
		{
			/* public function __call($method, $parameters)
            			{
            				return Arr::$method(...$parameters);
            			} */
		};
	}
}

if (!function_exists('st')) {
	function st($value)
	{
		if (!is_bool($value)) {
			return $value;
		}

		return $value ? 'true' : 'false';
	}
}

if (!function_exists('index_off_key')) {
	function index_off_key(array $array, $key): int
	{
		$index = -1;
		foreach (array_keys($array) as $k) {
			$index += 1;
			if ($key === $k) {
				return $index;
			}
		}

		return $index;
	}
}

if (!function_exists('get_array_diff')) {
	function get_array_diff($array1, $array2): array
	{
		$newArry = count($array1) > 0 ? array_diff($array1, $array2) : array_diff($array2, $array1);
		return array_values($newArry);
	}
}

if (!function_exists('get_dy_array_diff')) {
	function get_dy_array_diff($array1, $array2): array
	{
		$arrCount1 = count($array1);
		$arrCount2 = count($array2);
		if ($arrCount1 > 0 && $arrCount1 > $arrCount2) {
			$newArry = array_diff($array1, $array2);
		} elseif ($arrCount2 > 0) {
			$newArry = array_diff($array2, $array1);
		} else {
			$newArry = $array1;
		}

		return array_values($newArry);
	}
}

if (!function_exists('array_shared_values')) {
	function array_shared_values(array $array1, array $array2): array
	{
		return array_uniquex(array_merge(array_intersect($array1, $array2), array_intersect($array2, $array1)));
	}
}

if (!function_exists('array_has_array')) {
	function array_has_array(array $src_array, array $array2): bool
	{
		if ($array2 === []) {
			return true;
		}

		$array = collect($src_array)->mapWithKeys(fn($v, $k) => [
			$k => Str::lower($v),
		])->toArray();
		foreach ($array2 as $val) {
			if (in_array(Str::lower($val), $array)) {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('array_contains_all')) {
	function array_contains_all(array $src_array, array $array2): bool
	{
		$arr_count = count($array2);
		if ($arr_count === 0) {
			return true;
		}

		$_count = 0;
		foreach ($array2 as $val) {
			if (in_array($val, $src_array)) {
				$_count += 1;
			}
		}

		return $_count === $arr_count;
	}
}

if (!function_exists('key_sort_desc')) {
	function key_sort_desc(&$list)
	{
		$list = get_arrayable_items($list);
		krsort($list);
		return $list;
	}
}

if (!function_exists('json_array')) {
	/**
	 * @param array|null $arr
	 * @return string
	 */
	function json_array($arr, int $depth = 512)
	{
		return arr()->toJsonArray($arr, $depth);
	}
}
if (!function_exists('valid_json_array')) {

	function valid_json_array($data, int $depth = 512)
	{
		$array = toArray($data);
		return arr()->toJsonArray($array, $depth);
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

if (!function_exists('filter_array')) {
	function filter_array(array $array, ?callable $callback = null)
	{
		return ArrayHelper::filter($array, $callback);
	}
}

if (!function_exists('array_uniquex')) {
	function array_uniquex(array $array): array
	{
		if (!m_empty($array)) {
			$isAssoc = isAssoc($array);
			$array = array_unique($array, SORT_REGULAR);
			if (!$isAssoc) {
				$array = array_values($array);
			}
		}

		return $array;
	}
}
if (!function_exists('array_has_key')) {
	/**
	 * Easily check if  array key exist.
	 *
	 *
	 * @return boolean
	 */
	function array_has_key(array $arr, string $key)
	{
		if ($arr === []) {
			return false;
		}
		if (Arr::isAssoc($arr)) {
			return Arr::hasAny($arr, $key);
		}
		return collect($arr[0])->filter(function ($value, $k) use ($key): bool {
			return $k == $key || is_array($value) && array_key_exists($key, $value);
		})->count() > 0;
	}
}
if (!function_exists('arrayKeyExists')) {
	/**
	 * Easily check if  array key exist.
	 * @return boolean
	 */
	function arrayKeyExists(array $array, $key)
	{
		if ($array === []) {
			return false;
		}
		return array_key_exists($key, $array);
	}
}
if (!function_exists('array_keys_exists')) {
	/**
	 * Easily check if multiple array keys exist.
	 *
	 */
	function array_keys_exists(array $array,   ...$keys): bool
	{
		$count = arr()->count($keys, fn($key, $index) => array_key_exists($key, $array));
		return $count == count($keys);
	}
}


if (!function_exists('array_contains_keys')) {
	/**
	 * Easily check if multiple array contains at lest one key.
	 *
	 */
	function array_contains_keys(array $array, array $keys): bool
	{
		$ff = arr()->count($array, function ($v, $k) use ($keys): bool {
			return in_array($k, $keys);
		});
		return $ff > 0;
	}
}
if (!function_exists('array_empty')) {
	/**
	 * check if  array is null or count =0.
	 */
	function array_empty(array $array): bool
	{
		return (is_null($array) || count($array) == 0);
	}
}

if (!function_exists('compare_arrays')) {
	/**
	 * Easily check if  array  exist.
	 *
	 *
	 */
	function compare_arrays(array $array1, array $array2): bool
	{
		if ($array1 === [] && $array2 === []) {
			return true;
		}

		if (array_intersect($array1, $array2) !== []) {
			return true;
		}

		return array_intersect($array2, $array1) !== [];
	}
}

if (!function_exists('array_group_by_key')) {
	/**
	 * Group array by key
	 * @param mixed $array
	 * @param mixed $key
	 */
	function array_group_by_key(array $array, string $key): array
	{
		$array = get_arrayable_items($array);
		if (isArrayNullOrZero($array)) {
			return [];
		}

		$collect = collect($array);
		return $collect->mapToGroups(function ($col) use ($key) {
			$col = (array) $col;
			return [
				$col[$key] => $col,
			];
		})->mapWithKeys(fn($x, $k) => [
			$k => (object) $x[0],
		])->toArray();
	}
}

if (!function_exists('set_not_exists')) {
	/**
	 * set value to array if key not exists
	 *
	 * @param mixed $def_val
	 */
	function set_not_exists(array &$arr, string $key, $def_val = null): array
	{
		if (!isset($arr[$key])) {
			$arr[$key] = $def_val;
		}

		return $arr;
	}
}

if (!function_exists('pluck_array')) {
	/**
	 * pluck array
	 *
	 * @param array $array
	 * @param string $value
	 * @param string|null $key
	 * @return array
	 */
	function pluck_array($array, $value, $key = null)
	{
		if (count($array) > 0) {
			return Arr::pluck($array, $value, $key);
		}

		return [];
	}
}

if (!function_exists('pluck_distinct_array')) {
	/**
	 * pluck array
	 *
	 * @param array $array|\Illuminate\Support\Collection
	 * @param string $value
	 * @param string|null $key
	 * @return array
	 */
	function pluck_distinct_array($array, $value, $key = null)
	{
		/* if (!is_array($array))
        			$array = get_arrayable_items($array); */
		return collect($array)->pluck($value, $key)->unique()->values()->toArray();
	}
}

if (!function_exists('unset_exists')) {
	/**
	 * remove key from array
	 *
	 * @param string|int $key
	 * @return array
	 */
	function unset_exists(array &$arr, $key)
	{
		if (isset($arr[$key]) || array_key_exists($key, $arr)) {
			unset($arr[$key]);
		} elseif (isList($arr)) {
			$arr = array_values(arr()->where($arr, fn($value): bool => $value !== $key));
		}

		return $arr;
	}
}

if (!function_exists('unset_exist_keys')) {
	/**
	 * remove keys from array
	 *
	 * @return array
	 */
	function unset_exist_keys(array &$arr, array $keys)
	{
		foreach ($keys as $key) {
			$arr = unset_exists($arr, $key);
		}

		return $arr;
	}
}

if (!function_exists('serializeGroupedAssocCollect')) {
	/**
	 * @param Collection $collect
	 * @param boolean $pluck
	 * @return Collection
	 */
	function serializeGroupedAssocCollect($collect, $pluck = false)
	{
		$level = 0;
		$arr = $collect->mapWithKeys(function ($v, $k) use ($pluck, $level) {
			if ($v instanceof Arrayable) {
				$v = collect($v)->toArray();
			}

			$ser = serializeAssocArray($v, $pluck, 1);
			return [
				$k => $ser,
			];
		});
		$emptykeys = $arr->filter(function ($v, $k): bool {
			return is_array($v) && $v === [];
		})->count();
		if (count($arr) == $emptykeys) {
			return $arr->keys();
		}

		return $arr;
	}
}

if (!function_exists('serializeAssocArray')) {
	function serializeAssocArray($val, bool $pluck = false, $level = 0)
	{
		if (is_array($val) && !m_empty($val)) {
			if (Arr::isAssoc($val)) {
				if ($pluck && count($val) === 1) {
					$arrv = array_values($val);
					if ($level > 0 || !is_array($arrv[0])) {
						//	dd($arrv, $arrv[0]);
						return $arrv[0];
					}
				}

				$arr = collect($val)->mapWithKeys(function ($v, $k) use ($pluck, $level) {
					$ser = serializeAssocArray($v, $pluck, $level + 1);
					return [
						$k => $ser,
					];
				});
				$emptykeys = $arr->filter(function ($v, $k): bool {
					return is_array($v) && $v === [];
				})->count();
				if (count($arr) == $emptykeys) {
					return $arr->keys()->toArray();
				}

				return $arr->toArray();
			} elseif (count($val) === 1) {
				return serializeAssocArray($val[0], $pluck, $level + 1);
			} elseif (count($val) > 1) {
				$list = [];
				foreach ($val as $v) {
					$list[] = serializeAssocArray($v, $pluck, $level + 1);
				}

				return $list;
			}
		}

		return $val;
	}
}

if (!function_exists('arrayGroupAndSerializeBy')) {
	function arrayGroupAndSerializeBy(array $arry, string $groupKey, bool $pluck = false, $forget_key = true): array
	{
		return m_empty($arry) ? [] : serializeAssocArray(arrayGroupBy($arry, $groupKey, $forget_key), $pluck);
	}
}

if (!function_exists('arrayGroupAndSerializeByKeys')) {
	function arrayGroupAndSerializeByKeys(array $arry, array $groupKeys, bool $pluck = false, $forget_key = true): array
	{
		return m_empty($arry) ? [] : serializeAssocArray(arrayGroupByKeys($arry, $groupKeys, $forget_key), $pluck);
	}
}

if (!function_exists('arrayGroupBy')) {
	function arrayGroupBy(array $arry, string $groupKey, $forget_key = true, $level = 0): array
	{
		if (!m_empty($arry)) {
			try {
				if (array_has_key($arry, $groupKey)) {
					return collect($arry)->groupBy($groupKey)->mapWithKeys(function ($value, $key) use ($groupKey, $forget_key) {
						$dtl = collect($value)->mapWithKeys(function ($v, $k) use ($groupKey, $forget_key) {
							if ($forget_key && is_array($v)) {
								$v = unset_exists($v, $groupKey);
							}

							return [
								$k => $v,
							];
						})->toArray();
						return [
							$key => $dtl,
						];
					})->toArray();
				} elseif (Arr::isAssoc($arry)) {
					return collect($arry)->mapWithKeys(function ($value, $key) use ($groupKey, $forget_key, $level) {
						$dtl = arrayGroupBy($value, $groupKey, $forget_key, $level + 1);
						return [
							$key => $dtl,
						];
					})->toArray();
				}
			} catch (Throwable $th) {
				//throw $th;
			}
		}

		return $arry;
	}
}

if (!function_exists('arrayMGroupBy')) {
	function arrayMGroupBy(array $arry, string $groupKey, $forgetKey = true, $preserveKeys = false): array
	{
		if (!m_empty($arry)) {
			try {
				return (new MCollection($arry))->mxGroupBy($groupKey, $forgetKey, $preserveKeys)->toArray();
			} catch (Throwable $th) {
				//throw $th;
			}

			return arrayGroupBy($arry, $groupKey, $forgetKey);
		}

		return $arry;
	}
}



if (!function_exists('arrayGroupByKeys')) {
	function arrayGroupByKeys(array $arry, array $groupKeys, $forgetKey = true, $preserveKeys = false): array
	{
		
		$data = (new MCollection($arry))->mxGroupBy($groupKeys, $forgetKey, $preserveKeys)->toArray();

		return $data;
	}
}

if (!function_exists('getGroupedArrayKeys')) {
	function getGroupedArrayKeys(array $arry, array $groupKeys): array
	{
		$result = [];
		foreach ($groupKeys as $index => $key) {
			$res = collect($arry)->groupBy($key)->keys()->toArray();
			foreach ($res as $k) {
				if (!m_empty($k)) {
					$result[] = (object) [
						'key' => $key,
						'value' => $k,
						'index' => $index,
					];
				}
			}
		}

		return $result;
	}
}

if (!function_exists('array_max_key')) {
	function array_max_key(array $arr, int $incrment = 1): string
	{
		$max_key = $arr !== [] ? max(array_keys($arr)) : 0;
		/* 	if (key_exists($max_key, $arr)) {
          			return array_max_key($arr,$incrment+1);
          		} */
		return strval(intval($max_key) + $incrment);
	}
}

if (!function_exists('isList')) {
	function isList($value): bool
	{
		return is_array($value) && array_values($value) === $value;
	}
}

if (!function_exists('isAssoc')) {
	function isAssoc(array $arr): bool
	{
		$arr = get_arrayable_items($arr);
		return Arr::isAssoc($arr);
	}
}

if (!function_exists('isPluckArr')) {
	function isPluckArr(array $arr): bool
	{
		return $arr === [] || count(array_filter(array_keys($arr), 'is_int')) === count($arr);
	}
}

if (!function_exists('is_pluck_array')) {
	function is_pluck_array($array)
	{
		$array = get_arrayable_items($array);
		if (!is_array($array)) {
			$array = $array->toArray();
		}

		if (count($array) <= 0) {
			return false;
		}

		return isPluckArr($array);
		//return count(array_filter(array_keys($array), 'is_int')) > 0;
	}
}

if (!function_exists('isArrayNullOrZero')) {
	/**
	 * Prepares and returns if array is null or empty.
	 *
	 * @param array
	 * @return boolean
	 */
	function isArrayNullOrZero($value)
	{
		if (!is_array($value)) {
			if (is_null($value)) {
				return true;
			}

			$value = get_arrayable_items($value);
		}

		try {
			return is_null($value) || empty($value) || !isset($value) || count($value) === 0;
		} catch (Throwable $throwable) {
			
			throw $throwable;
		}
	}
}

if (!function_exists('arrayParseKeysPath')) {
	function arrayParseKeysPath($array, $exceptKeys = [], $separator = '.'): array
	{
		if (is_null($array)) {
			$array = [];
		}

		if (is_null($exceptKeys)) {
			$exceptKeys = [];
		}

		$result = array();
		foreach ($array as $path => $value) {
			$temp = &$result;
			foreach (explode($separator, (string) $path) as $key) {
				if (!in_array($key, $exceptKeys)) {
					$temp = &$temp[$key];
				}
			}

			$temp = $value;
		}

		return $result;
	}
}

if (!function_exists('explode_str')) {
	function explode_str(string $str, array|string $separator): array
	{
		$separator = toArray($separator);
		if (m_empty($str)) {
			return [];
		}

		if ($separator === []) {
			return [$str];
		}

		$newStr = str_replace($separator, $separator[0], $str);
		$arr = explode($separator[0], $newStr);
		return array_values(Arr::where($arr, fn($value): bool => !empty($value)));
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

if (!function_exists('clone_array')) {
	function clone_array(array $arr)
	{
		if (m_empty($arr)) {
			return [];
		}

		
		return collect($arr)->mapWithKeys(function ($v, $k) {
			if (is_array($v)) {
				return [
					$k => clone_array($v),
				];
			} elseif (is_object($v)) {
				return [
					$k => clone $v,
				];
			} else {
				return [
					$k => $v,
				];
			}
		})->toArray();
	}
}

if (!function_exists('merge_array')) {
	function merge_array(array &$src_array, ...$arrays): array
	{
		foreach ($arrays as $arr) {
			if (is_array($arr)) {
				$src_array = merge_array($src_array, ...$arr);
			} else {
				$src_array[] = $arr;
			}
		}

		return $src_array;
	}
}

if (!function_exists('merge_distinct_array')) {
	function merge_distinct_array(array &$src_array, ...$arrays): array
	{
		$src_array = merge_array($src_array, $arrays);
		return collect($src_array)->sort()->unique()->values()->toArray();
	}
}

if (!function_exists('merge_assoc_array')) {
	/**
	 * @return mixed[]
	 */
	function merge_assoc_array(array $defArray, array $newArray, $escape = true): array
	{
		$defArray = isArrayNullOrZero($defArray) ? [] : $defArray;
		$newArray = isArrayNullOrZero($newArray) ? [] : $newArray;
		foreach ($defArray as $key => $value) {
			try {
				$new_val = $newArray[$key] ?? null;
				if (!m_empty($new_val) && (is_array($new_val) || is_array($value) && isAssoc($value))) {
					$new_val = arr()->wrap($new_val);
					$value = arr()->wrap($value);
					//dd($key, $value, $new_val);
					$newArray[$key] = merge_assoc_array($value, $new_val);
				} elseif (!array_key_exists($key, $newArray)) {
					$newArray[$key] = $value;
				} elseif (!$escape) {
					if (is_null($new_val)) {
						$newArray[$key] = $value;
					} elseif (is_array($new_val)) {
						if (is_array($value)) {
							$newArray[$key] = merge_array($new_val, $value);
						} else {
							$newArray[$key][] = $value;
						}
					} elseif (is_string($new_val) && $new_val !== $value) {
						$newArray[$key] .= ' ' . $value;
					}
				}
			} catch (Exception $th) {
			
			}
		}

		return $newArray;
	}
}

if (!function_exists('extend_muldim_array')) {
	function extend_multydim_array($defArray, $newArray)
	{
		$defArray = get_arrayable_items($defArray);
		$defArray = isArrayNullOrZero($defArray) ? [] : $defArray;

		$arry = get_arrayable_items($newArray);
		$arry = isArrayNullOrZero($arry) ? [] : $arry;
		foreach ($defArray as $key => $value) {
			try {
				if (is_string($key)) {
					if (!array_key_exists($key, $arry)) {
						$arry[$key] = is_array($value) ? [] : $value;
					}
				} elseif (!in_array($value, $arry)) {
					$arry[] = $value;
				}
			} catch (Exception $th) {
				
			}
		}

		return $arry;
	}
}

if (!function_exists('get_arrayable_items')) {
	function get_arrayable_items($items)
	{
		if (m_empty($items)) {
			return [];
		}

		if (is_array($items)) {
			return $items;
		}

		return match (true) {
			$items instanceof WeakMap => throw new InvalidArgumentException('Collections can not be created using instances of WeakMap.'),
			$items instanceof Enumerable => $items->all(),
			$items instanceof Arrayable => $items->toArray(),
			$items instanceof Traversable => iterator_to_array($items),
			$items instanceof Jsonable => json_decode($items->toJson(), true),
			$items instanceof JsonSerializable => (array) $items->jsonSerialize(),
			$items instanceof UnitEnum => [$items],
			default => (array) $items,
		};
	}
}
