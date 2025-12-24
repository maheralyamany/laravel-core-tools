<?php

declare(strict_types=1);

namespace Maher\CoreTools\Support;

use Throwable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Arr;
use UnexpectedValueException;
use function ksort;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 */
class ArrayHelper
{
	/**
	 * دمج مصفوفتين مع إستراتيجية محددة
	 */
	public static function merge($array1, $array2, $strategy = 'overwrite')
	{
		return self::mergeRecursiveCustom($array1, $array2, $strategy);
	}

	/**
	 * Run a filter over each of the items.
	 *@param array $array
	 * @param  (callable(TValue, TKey): bool)|null  $callback
	 * @return array
	 */
	public static function filter(array $array, ?callable $callback = null)
	{
		if (!m_empty($array)) {
			$isAssoc = isAssoc($array);
			$arr = $callback ? Arr::where($array, $callback) : array_filter($array);
			if (!$isAssoc) {
				$arr = array_values($arr);
			}
			return $arr;
		}
		return [];
	}

	/**
	 * Run an associative map over each of the items.
	 *
	 * The callback should return an associative array with a single key/value pair.
	 *
	 * @template TMapWithKeysKey of array-key
	 * @template TMapWithKeysValue
	 * @template TKey of array-key
	 * @template-covariant TValue
	 * @param  array<TKey, TValue> $array
	
	 * @param  callable(TValue, TKey): array<TMapWithKeysKey, TMapWithKeysValue>|null  $callback
	 * @param bool $array_values
	 * @return array<TMapWithKeysKey, TMapWithKeysValue>
	 */
	public static function filterMapWithKeys($array, $callback, $array_values = false)
	{
		$items = [];
		$pushToItems = function ($key, $value) use (&$items, $array_values) {
			if (!$array_values || !\is_numeric($key))
				$items[$key] = $value;
			else {
				$items[count($items)] = $value;
			}
			return $items;
		};
		foreach ($array as $key => $value) {
			// --- Select / map callback ---
			if ($callback) {
				$mapped = $callback($value, $key);
				if (!m_empty($mapped)) {
					if (is_array($mapped)) {
						foreach ($mapped as $k => $val) {
							$items = $pushToItems($k, $val);
						}
					} else {
						$items = $pushToItems($key, $mapped);
					}
				}
			} else {
				$items = $pushToItems($key, $value);
			}
		}
		return $items;
	}


	/**
	 * Run a filter over each of the items.
	 * @param array $array
	 * @param  (callable(TValue, TKey): bool)  $callback
	 * @return int
	 */
	public static function count(array $array, callable $callback): int
	{
		$filterd = self::filter($array, $callback);
		return count($filterd);
	}
	/**
	 * Sort the collection keys.
	 * @param array $array
	 * @param  int  $options
	 * @param  bool  $descending
	 * @return array
	 */
	public static function sortKeys(array $array, $options = SORT_REGULAR, $descending = false)
	{
		$items = $array;
		$descending ? krsort($items, $options) : ksort($items, $options);
		return $items;
	}
	/**
	 * Sort the collection keys.
	 * @param array $array
	 * @param  int  $options
	 * @param  bool  $descending
	 * @return array
	 */
	public static function sortKeysDesc(array $array, $options = SORT_REGULAR)
	{
		return self::sortKeys($array, $options, true);
	}
	/**
	 * الدمج الأساسي مع الإستراتيجيات
	 */
	public static function mergeRecursiveCustom($array1, $array2, $strategy = 'overwrite')
	{
		// Validate inputs
		if (!is_array($array1) || !is_array($array2)) {
			throw new InvalidArgumentException('Both parameters must be arrays');
		}

		// نسخ المصفوفة الأولى لتجنب التعديل على الأصلية
		$merged = $array1;
		foreach ($array2 as $key => $value) {
			// إذا كان المفتاح موجوداً في المصفوفة الأولى
			if (array_key_exists($key, $merged)) {
				// إذا كانت القيمتان مصفوفتين، ندمج بشكل متكرر
				if (is_array($merged[$key]) && is_array($value)) {
					$merged[$key] = self::mergeRecursiveCustom($merged[$key], $value, $strategy);
				} else {
					$merged[$key] = self::applyStrategy($merged[$key], $value, $strategy, $key);
				}
			} else {
				$merged[$key] = $value;
			}
		}

		return $merged;
	}

	/**
	 * نسخة متقدمة مع خيارات إضافية
	 */
	public static function mergeAdvanced($array1, $array2, $options = [])
	{
		// Validate inputs
		if (!is_array($array1) || !is_array($array2)) {
			throw new InvalidArgumentException('Both parameters must be arrays');
		}

		$defaultOptions = [
			'strategy' => 'overwrite',
			'array_merge_strategy' => 'merge',
			// 'merge' أو 'replace'
			'numeric_keys' => 'preserve',
			// 'preserve' أو 'overwrite'
			'deep_merge' => true,
		];
		$options = array_merge($defaultOptions, $options);
		return self::mergeRecursiveWithOptions($array1, $array2, $options);
	}

	/**
	 * دمج متعدد المصفوفات
	 */
	public static function mergeMultiple($strategy = 'overwrite', ...$arrays)
	{
		if (empty($arrays)) {
			return [];
		}

		$result = $arrays[0];
		$counter = count($arrays);
		for ($i = 1; $i < $counter; $i++) {
			$result = self::mergeRecursiveCustom($result, $arrays[$i], $strategy);
		}

		return $result;
	}

	public static function toJsonArray(array|object|null $data, int $depth = 512)
	{
		$array = get_arrayable_items($data);
		//JSON_FORCE_OBJECT
		//$json = json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		$json = json_encode($array, JSON_UNESCAPED_UNICODE, $depth);
		return stripslashes($json);
	}

	public static function toJson(array|object|null $data, int $depth = 512)
	{
		if (\m_empty($data)) {
			return '{}';
		}

		$array = get_arrayable_items($data);
		/* if (is_array($array) || is_object($data)) {
         		} */
		return stripslashes(json_encode($array, JSON_FORCE_OBJECT, $depth));
	}

	/**
	 * دمج مع التحقق من الأنواع
	 */
	public static function mergeWithValidation($array1, $array2, $strategy = 'overwrite', $allowedTypes = null)
	{
		if ($allowedTypes !== null) {
			self::validateTypes($array1, $allowedTypes);
			self::validateTypes($array2, $allowedTypes);
		}

		return self::mergeRecursiveCustom($array1, $array2, $strategy);
	}

	public static function getFormattedStr(array $array, $withDigitKeys = true): string
	{
		$content = var_export($array, true);
		$content = Str::replace("array (", "[", $content);
		$content = Str::replace("),", "],", $content);
		$content = Str::replaceEnd(")", "]", $content);
		if (!$withDigitKeys) {
			$content = preg_replace('/(\d+) =>/', '', $content);
		}

		//$content = Str::replace("\n", "", $content);
		return '<?php return ' . $content . ';';
	}

	/**
	 * تطبيق الإستراتيجية على القيم
	 */
	private static function applyStrategy($value1, $value2, $strategy, $key = null)
	{
		switch ($strategy) {
			case 'overwrite':
				// استبدال القيمة الأولى بالثانية
				return $value2;
			case 'keep_first':
				// الحفاظ على القيمة الأولى
				return $value1;
			case 'concatenate':
				// دمج النصوص أو إنشاء مصفوفة
				return self::concatenateValues($value1, $value2);
			case 'sum':
				// جمع القيم الرقمية
				return self::sumValues($value1, $value2);
			default:
				return $value2;
		}
	}

	/**
	 * دمج القيم (إستراتيجية concatenate)
	 */
	private static function concatenateValues($value1, $value2)
	{
		// إذا كانت القيمتان نصيتين
		if (is_string($value1) && is_string($value2)) {
			return $value1 . ' ' . $value2;
		}

		// إذا كانت القيمتان مصفوفتين
		if (is_array($value1) && is_array($value2)) {
			return array_merge($value1, $value2);
		}

		// إذا كانت إحداهما مصفوفة والأخرى لا
		if (is_array($value1) && !is_array($value2)) {
			$value1[] = $value2;
			return $value1;
		}

		if (!is_array($value1) && is_array($value2)) {
			array_unshift($value2, $value1);
			return $value2;
		}

		// إذا لم تكن نصاً أو مصفوفة، نعيد القيم في مصفوفة
		return [$value1, $value2];
	}

	/**
	 * جمع القيم (إستراتيجية sum)
	 */
	private static function sumValues($value1, $value2)
	{
		// إذا كانت القيمتان رقميتين
		if (is_numeric($value1) && is_numeric($value2)) {
			return $value1 + $value2;
		}

		// إذا كانت القيمتان مصفوفتين من الأرقام
		if (is_array($value1) && is_array($value2)) {
			return array_merge($value1, $value2);
		}

		// إذا لم يمكن جمعهما، نستبدل بالقيمة الثانية
		return $value2;
	}

	/**
	 * الدمج مع الخيارات المتقدمة
	 */
	private static function mergeRecursiveWithOptions($array1, $array2, $options)
	{
		$merged = $array1;
		foreach ($array2 as $key => $value) {
			$isNumericKey = is_numeric($key);
			// معالجة المفاتيح الرقمية
			if ($isNumericKey && $options['numeric_keys'] === 'preserve') {
				$merged[] = $value;
				continue;
			}

			if (array_key_exists($key, $merged)) {
				if (is_array($merged[$key]) && is_array($value) && $options['deep_merge']) {
					$merged[$key] = self::mergeRecursiveWithOptions($merged[$key], $value, $options);
				} else {
					$merged[$key] = self::applyAdvancedStrategy($merged[$key], $value, $options['strategy'], $key);
				}
			} else {
				$merged[$key] = $value;
			}
		}

		return $merged;
	}

	/**
	 * تطبيق الإستراتيجيات المتقدمة
	 */
	private static function applyAdvancedStrategy($value1, $value2, $strategy, $key = null)
	{
		switch ($strategy) {
			case 'overwrite':
				return $value2;
			case 'keep_first':
				return $value1;
			case 'concatenate':
				return self::concatenateValues($value1, $value2);
			case 'sum':
				return self::sumValues($value1, $value2);
			case 'average':
				if (is_numeric($value1) && is_numeric($value2)) {
					return ($value1 + $value2) / 2;
				}

				return $value2;
			case 'max':
				if (is_numeric($value1) && is_numeric($value2)) {
					return max($value1, $value2);
				}

				return $value2;
			case 'min':
				if (is_numeric($value1) && is_numeric($value2)) {
					return min($value1, $value2);
				}

				return $value2;
			default:
				return $value2;
		}
	}

	/**
	 * التحقق من أنواع البيانات
	 */
	private static function validateTypes($array, $allowedTypes)
	{
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				self::validateTypes($value, $allowedTypes);
			} else {
				$type = gettype($value);
				if (!in_array($type, $allowedTypes)) {
					throw new InvalidArgumentException(sprintf("النوع غير مسموح للمفتاح '%s': %s. الأنواع المسموحة: ", $key, $type) . implode(', ', $allowedTypes));
				}
			}
		}
	}

	public static function chunkAssociative(array $array, int $size = 0)
	{
		if (count($array) <= $size) {
			return [$array];
		}

		$result = [];
		$count = 0;
		$temp = [];
		foreach ($array as $key => $value) {
			$temp[$key] = $value;
			$count++;
			if ($count % $size === 0) {
				$result[] = $temp;
				$temp = [];
			}
		}

		// Add the remaining items
		if (!empty($temp)) {
			$result[] = $temp;
		}

		return $result;
	}

	public static function getColumns(array $array1)
	{
		if (empty($array1)) {
			return [];
		}

		$row = Arr::isAssoc($array1) ? $array1 : $array1[0];
		return array_keys($row);
	}

	public static function arrayUnGroupRecursive(array $groupData, array $groups, array &$allData, array $curRow = []): void
	{
		if (!empty($groups)) {
			$key = Arr::pull($groups, 0);
			if (!empty($groups)) {
				$groups = array_values($groups);
			}

			//
			try {
				foreach ($groupData as $groupVal => $rows) {
					$curRow[$key] = $groupVal;
					self::arrayUnGroupRecursive($rows, $groups, $allData, $curRow);
				}

				//dd($groupData, $allData, $curRow);
			} catch (Throwable $th) {
				dd($key, $groupData, $groups, $allData, $curRow);
				//throw $th;
			}
		} elseif (!empty($groupData)) {
			/* 	if (!isset($groupData[0]))
            				dd($groupData, $groups, $curRow); */
			foreach ($groupData as $row) {
				$newRow = array_merge($curRow, $row);
				$allData[] = $newRow;
			}

			//dd($groupData, $groups,$curRow,$sums);
		}

		/* else
        			dd($groupData, $allData, $curRow); */
	}

	public static function getSharedColumns(array $array1, array $array2): array
	{
		$firstColumns = self::getColumns($array1);
		$secondColumns = self::getColumns($array2);
		$sharedColumns = array_shared_values($firstColumns, $secondColumns);
		return [$firstColumns, $secondColumns, $sharedColumns];
	}

	public static function arrayDeepReplaceRecursive(array $array1, array $array2): array
	{
		if (empty($array1) ) {
			return $array2;
		} elseif (empty($array2)) {
			return $array1;
		}

		[$firstColumns, $secondColumns, $sharedColumns] = self::getSharedColumns($array1, $array2);
		$allData = [];
		if (count($sharedColumns) > 0) {
			$array1 = arrayGroupByKeys($array1, $sharedColumns, true);
			$array2 = arrayGroupByKeys($array2, $sharedColumns, true);
			$groupData = array_replace_recursive($array1, $array2);
			self::arrayUnGroupRecursive($groupData, $sharedColumns, $allData, []);
		} else {
			$allData = array_replace_recursive($array1, $array2);
		}

		return $allData;
	}

	public static function arrayMergeRecursive($array1, $array2): array
	{
		return array_merge_recursive($array1, $array2);
	}

	public static function arrayReplaceOrAdd(array $array, array $replacement): array
	{
		foreach ($replacement as $key => $value) {
			$array[$key] = $value;
		}

		return $array;
	}

	public static function arrayReplaceRecursive($array1, $array2): array
	{
		return array_replace_recursive($array1, $array2);
	}

	/**
	 * @return float[]|int[]|numeric-string[]
	 */
	public static function array_sum_identical_keys(...$arrays): array
	{
		$merged = array();
		foreach ($arrays as $array) {
			foreach ($array as $key => $value) {
				if (!is_numeric($value)) {
					continue;
				}

				if (!isset($merged[$key])) {
					$merged[$key] = $value;
				} else {
					$merged[$key] += $value;
				}
			}
		}

		return $merged;
	}

	/**
	 * Remove one or many array items from a given array using "dot" notation.
	 *
	 * @param  array  $items
	 * @param  array|string|int|float  $keys
	 * @return array
	 */
	public static function forgetKey($items, $keys)
	{
		$keys = arr()->wrap($keys);
		if (count($keys) > 0) {
			if (isAssoc($items)) {
				arr()->forget($items, $keys);
			} else {
				$items = collect($items)->mapWithKeys(function ($row, $k) use ($keys) {
					arr()->forget($row, $keys);
					return [
						$k => $row,
					];
				})->toArray();
			}
		}

		return $items;
	}

	/**
	 * Append a new value to the end of array
	 *
	 * @param array $array A array that will  append to
	 * @param mixed $value A value to be appended
	 */
	public static function append(array &$array, $value): array
	{
		$array[] = $value;
		return $array;
	}

	public static function appendAssoc(array &$array, array $insert_array): array
	{
		$array += $insert_array;
		return $array;
	}

	public static function prependAssoc(array &$array, array $insert_array)
	{
		if (!empty($insert_array)) {
			$array = $insert_array + $array;
		}

		return $array;
	}

	/**
	 * Attach a value to the top of array
	 *
	 * @param array $array A array that will  prepend to
	 * @param mixed $value A value to be prepend
	 */
	public static function prepend(array &$array, $value): array
	{
		if ($value) {
			array_unshift($array, $value);
		}

		return $array;
	}

	/**
	 * Insert value of data at any position
	 * @param array $array A array that will  insert to
	 * @param mixed $value A value to be inserted
	 * @param integer $position A position
	 *
	 * @return array
	 */
	public static function insert(array &$array, $value, $position)
	{
		if ($position <= 0) {
			$array = static::prepend($array, $value);
		} elseif ($position >= count($array)) {
			$array = static::append($array, $value);
		} else {
			array_splice($array, $position, 0, [$value]);
		}

		return $array;
	}

	public static function insertAssoc(array &$array, array $insert_array, $position)
	{
		if ($position <= 0) {
			$array = static::prependAssoc($array, $insert_array);
		} elseif ($position >= count($array)) {
			$array = static::appendAssoc($array, $insert_array);
		} else {
			$array = array_merge(array_slice($array, 0, $position, true), $insert_array, array_slice($array, $position, null, true));
		}

		return $array;
	}

	/**
	 * Insert value of data before key
	 * @param array $array A array that will  insert to
	 * @param mixed $value A value to be inserted
	 * @param string $key A key that will be insert before
	 *
	 * @return array
	 */
	public static function insertBefore(array &$array, $value, $key)
	{
		$position = static::indexOfKey($array, $key);
		$array = static::insert($array, $value, $position);
		return $array;
	}

	/**
	 * Insert value of data After key
	 * @param array $array A array that will  insert to
	 * @param mixed $value A value to be inserted
	 * @param string $key A key that will be insert After
	 *
	 * @return array
	 */
	public static function insertAfter(array &$array, $value, $key)
	{
		$position = static::indexOfKey($array, $key) + 1;
		$array = static::insert($array, $value, $position);
		return $array;
	}

	/**
	 * Insert insert_array  of data After key
	 * @param array $array A array that will  insert to
	 * @param array $insert_array  A array to be inserted
	 * @param string $key A key that will be insert After
	 *
	 * @return array
	 */
	public static function insertAssocAfterKey(array &$array, array $insert_array, $key)
	{
		$index = static::indexOfKey($array, $key);
		$array = static::insertAssoc($array, $insert_array, $index + 1);
		return $array;
	}

	/**
	 * Insert insert_array  of data Before key
	 * @param array $array A array that will  insert to
	 * @param array $insert_array  A array to be inserted
	 * @param string $key A key that will be insert Before
	 *
	 * @return array
	 */
	public static function insertAssocBeforeKey(array &$array, array $insert_array, $key)
	{
		$index = static::indexOfKey($array, $key);
		$array = static::insertAssoc($array, $insert_array, $index);
		return $array;
	}

	/**
	 *
	 *
	 * @param array|Collection $array
	 * @param string $key
	 */
	public static function indexOfKey($array, $key): int
	{
		$index = array_search($key, array_keys($array), true);
		if ($index === false) {
			$index = -1;
		}

		return $index;
	}

	/**
	 *
	 *
	 * @param array|Collection $array
	 * @param string $key
	 */
	public static function indexOf($array, $key): int
	{
		$index = -1;
		collect($array)->each(function ($name, $i) use (&$index, $key): void {
			if ($name === $key && $index == -1) {
				$index = $i;
			}
		});
		return $index;
	}

	/**
	 * Computes the difference of multidimensional arrays using keys for comparison.
	 */
	public static function multidimensionalDiffByKeys(array $first, array $second): array
	{
		if ($diff = array_diff_key($first, $second)) {
			return $diff;
		} else {
			foreach ($first as $key => $value) {
				if (is_array($value) && $diff = static::multidimensionalDiffByKeys($value, $second[$key])) {
					return [
						$key => $diff,
					];
				}
			}
		}

		return [];
	}

	/**
	 * Summary of arrayDiffAssocMultidimensional
	 * @param array $array1
	 * @param array $array2
	 * @param mixed $withCompareValue
	 * @return array
	 */
	public static function arrayDiffAssocMultidimensional(array $array1, array $array2, $withCompareValue = true): array
	{
		$difference = [];
		foreach ($array1 as $key => $value) {
			if (!$withCompareValue) {
				if (!array_key_exists($key, $array2)) {
					$difference[$key] = $value;
				}
			} elseif (is_array($value)) {
				if (!array_key_exists($key, $array2)) {
					$difference[$key] = $value;
				} elseif (!is_array($array2[$key])) {
					$difference[$key] = $value;
				} else {
					$multidimensionalDiff = static::arrayDiffAssocMultidimensional($value, $array2[$key], $withCompareValue);
					if (!empty($multidimensionalDiff)) {
						$difference[$key] = $multidimensionalDiff;
					}
				}
			} elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
				$difference[$key] = $value;
			}
		}

		return $difference;
	}

	public static function arrayDyffDyAssoc(array $array1, array $array2, $arrKey = 'id'): array
	{
		$diff1 = ArrayHelper::arrayDiffAssocKeys($array1, $array2, $arrKey);
		$diff2 = ArrayHelper::arrayDiffAssocKeys($array2, $array1, $arrKey);
		return collect($diff1)->merge($diff2)->unique()->toArray();
	}

	public static function arrayDiffAssocKeys(array $array1, array $array2, $arrKey = 'id'): array
	{
		$difference = [];
		$addDifference = function ($key, $value) use (&$difference, $array1, $arrKey): void {
			if ($key != $arrKey && array_key_exists($arrKey, $array1) && !array_key_exists($arrKey, $difference)) {
				$difference[$arrKey] = $array1[$arrKey];
			}

			$difference[$key] = $value;
		};
		collect($array1)->each(function ($value, $key) use ($array2, $arrKey, $addDifference): void {
			if (is_array($value)) {
				$otherArr = $array2[$key] ?? [];
				if (count($otherArr) === 0) {
					$addDifference($key, $value);
				} elseif (!is_array($otherArr)) {
					$addDifference($key, $value);
				} else {
					$multidimensionalDiff = static::arrayDiffAssocKeys($value, $otherArr, $arrKey);
					if (!empty($multidimensionalDiff)) {
						$addDifference($key, $multidimensionalDiff);
					}
				}
			} else {
				$otherval = $array2[$key] ?? null;
				if ($otherval != $value) {
					$addDifference($key, $value);
				}
			}
		});
		return $difference;
	}

	public static function multidimensionalSortByKeys(array $array): array
	{
		ksort($array);
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$array[$key] = static::multidimensionalSortByKeys($value);
			}
		}

		return $array;
	}

	// пока что умеет работать только с десятком $items
	public function getItemByStringEnd(string $string, array $items): string
	{
		$partsQuantity = count($items);
		if ($partsQuantity === 1) {
			return reset($items);
		}

		if ($partsQuantity !== 10) {
			throw new UnexpectedValueException('The number of items must be 10');
		}

		// На 64-битных платформах все результаты crc32() будут положительными целыми.
		$key = mb_substr((string) crc32($string), -1);
		return $items[$key];
	}
}
