<?php

declare(strict_types=1);

namespace Maher\CoreTools\Support;

use RuntimeException;

class ArrayToPhpConverter
{
	/**
	 * تحويل المصفوفة إلى نص PHP قابل للحفظ في ملف
	 * @param array $array
	 * @param array $options
	 * @return string
	 */
	public static function toPhpString(array $array, array $options = [])
	{
		$defaultOptions = [
			'include_php_tag' => true,
			'indentation' => '    ',
			'max_depth' => 50,
			'show_types' => false,
			'ignore_numeric_keys' => false,
			'only_letters' => false,
		];
		$options = array_merge($defaultOptions, $options);
		$phpCode = self::convertArrayToPhp($array, $options, 0);
		if ($options['include_php_tag']) {
			return "<?php\n\nreturn " . $phpCode . ";";
		}

		return "return " . $phpCode . ";";
	}

	/**
	 *  حفظ المصفوفة في ملف PHP
	 * @param array $array
	 * @param string $filename
	 * @param array $options
	 * @param int $mode
	 * @param bool $recursive
	 * @throws RuntimeException
	 * @return bool
	 */
	public static function saveToFile(array $array, string $filename, array $options = [], int $mode = 0755, bool $recursive = true)
	{
		$phpCode = self::toPhpString($array, $options);
		// التأكد من وجود المجلد
		FileHelper::makeDirectoryIFNotExists($filename, $mode, $recursive);
		$result = FileHelper::put($filename, $phpCode, true);
		if ($result === false) {
			throw new RuntimeException("Failed to save array to file: " . $filename);
		}

		return true;
	}

	/**
	 * تحويل المصفوفة إلى نص PHP مع تنسيق مضغوط
	 * @param array $array
	 * @param array $options
	 * @return string
	 */
	public static function toCompactPhpString(array $array, array $options = [])
	{
		$defaultOptions = [
			'include_php_tag' => true,
			'indentation' => '',
			'max_depth' => 5,
			'show_types' => false,
			'ignore_numeric_keys' => false,
			'only_letters' => false,
		];
		$options = array_merge($defaultOptions, $options);
		$phpCode = self::convertArrayToCompact($array, $options, 0);
		if ($options['include_php_tag']) {
			return "<?php return " . $phpCode . ";";
		}

		return "return " . $phpCode . ";";
	}

	/**
	 * تحويل المصفوفة مع الاحتفاظ بالقيم فقط (بدون مفاتيح)
	 * @param array $array
	 * @param array $options
	 * @return string
	 */
	public static function toValuesOnlyPhpString(array $array, array $options = [])
	{
		$defaultOptions = [
			'include_php_tag' => true,
			'indentation' => '    ',
			'max_depth' => 10,
			'show_types' => false,
		];
		$options = array_merge($defaultOptions, $options);
		$phpCode = self::convertArrayToValuesOnly($array, $options, 0);
		if ($options['include_php_tag']) {
			return "<?php\nreturn " . $phpCode . ";";
		}

		return "return " . $phpCode . ";";
	}

	/**
	 * تحويل المصفوفة بشكل متكرر
	 */
	private static function convertArrayToPhp($array, $options, $depth)
	{
		if ($depth > $options['max_depth']) {
			//dd($array, $depth);
			return "[] // Max depth reached";
		}

		if (empty($array)) {
			return "[]";
		}

		$indent = str_repeat($options['indentation'], $depth);
		$innerIndent = str_repeat($options['indentation'], $depth + 1);
		$lines = [];
		$lines[] = "[";
		foreach ($array as $key => $value) {
			// تجاهل المفاتيح الرقمية إذا كان الخيار مفعلاً
			if ($options['ignore_numeric_keys'] && is_numeric($key) || ($options['only_letters'] && !StringValidator::containsLetters($key))) {
				continue;
			}

			$keyString = self::formatKey($key, $options);
			$valueString = self::formatValue($value, $options, $depth + 1);
			$lines[] = $innerIndent . self::formatRowValue($keyString, $key, $valueString) . ',';
		}

		$lines[] = $indent . "]";
		return implode("\n", $lines);
	}

	private static function addStrSlashes($value)
	{
		if (is_object($value)) {
			return \addslashes(serialize($value));
		} elseif (!is_string($value)) {
			$value = (string) $value;
			// return addslashes($value);
		}

		/* if (str_contains($value, self::SINGLEQOUTATION)) {
               $value = \str_replace("\'", "'", $value);
               //dd($value, addslashes($value), \str_replace("\'", "'", $value));
               return addslashes($value);
           } */
		return $value;
	}

	public const QOUTATION = '"';

	public const SINGLEQOUTATION = "'";

	public static function addQoutation(string $value)
	{
		if (str_contains($value, self::SINGLEQOUTATION) && str_contains($value, self::QOUTATION)) {
			$value = \str_replace("\\'", "'", $value);
			$value = \str_replace('\"', '"', $value);
			//dd(addslashes($value), $value);
			$value = addslashes($value);
		}

		if (str_contains($value, self::SINGLEQOUTATION)) {
			return self::QOUTATION . $value . self::QOUTATION;
		}

		return self::SINGLEQOUTATION . $value . self::SINGLEQOUTATION;
	}

	/**
	 * تنسيق المفتاح
	 */
	private static function formatKey($key, $options)
	{
		if (is_int($key)) {
			return $key;
		}

		if (is_string($key)) {
			// التحقق إذا كان المفتاح يحتاج إلى اقتباس
			if (preg_match('/^[a-zA-Z_]\w*$/', $key)) {
				return self::addQoutation($key);
			} else {
				return self::addQoutation(self::addStrSlashes($key));
			}
		}

		return self::addQoutation(self::addStrSlashes((string) $key));
	}

	/**
	 * تنسيق القيمة
	 */
	private static function formatValue($value, $options, $depth)
	{
		if (is_null($value)) {
			return 'null' . ($options['show_types'] ? " // null" : "");
		}

		if (is_array($value)) {
			return self::convertArrayToPhp($value, $options, $depth);
		}

		if (is_string($value)) {
			return self::addQoutation(self::addStrSlashes($value)) . ($options['show_types'] ? " // string" : "");
		}

		if (is_int($value)) {
			return $value . ($options['show_types'] ? " // int" : "");
		}

		if (is_float($value)) {
			// التحقق إذا كان الرقم عدداً صحيحاً
			if ($value == (int) $value) {
				return $value . '.0' . ($options['show_types'] ? " // float" : "");
			}

			return $value . ($options['show_types'] ? " // float" : "");
		}

		if (is_bool($value)) {
			return ($value ? 'true' : 'false') . ($options['show_types'] ? " // bool" : "");
		}

		if (is_object($value)) {
			$type = get_class($value);
			return "unserialize(" . self::addQoutation(self::addStrSlashes($value)) . ")" . ($options['show_types'] ? sprintf(' // object(%s)', $type) : "");
		}

		return var_export($value, true);
	}

	/**
	 * تحويل مضغوط للمصفوفة
	 */
	private static function convertArrayToCompact($array, $options, $depth)
	{
		if ($depth > $options['max_depth']) {
			return "[]";
		}

		if (empty($array)) {
			return "[]";
		}

		$items = [];
		foreach ($array as $key => $value) {
			// تجاهل المفاتيح الرقمية إذا كان الخيار مفعلاً
			if ($options['ignore_numeric_keys'] && is_numeric($key)) {
				continue;
			}

			$keyString = self::formatKey($key, $options);
			$valueString = self::formatCompactValue($value, $options, $depth + 1);
			$items[] = self::formatRowValue($keyString, $key, $valueString);
		}

		return '[' . implode(',', $items) . ']';
	}
	private static function formatRowValue($keyString, $key, $valueString)
	{
		if (is_int($key))
			return $valueString;
		return $keyString . '=>' . $valueString;
	}
	/**
	 * تنسيق مضغوط للقيمة
	 */
	private static function formatCompactValue($value, $options, $depth)
	{
		if (is_array($value)) {
			return self::convertArrayToCompact($value, $options, $depth);
		}

		if (is_string($value)) {
			return self::addQoutation(self::addStrSlashes($value));
		}

		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}

		if (is_null($value)) {
			return 'null';
		}

		return var_export($value, true);
	}

	/**
	 * تحويل المصفوفة إلى قيم فقط
	 */
	private static function convertArrayToValuesOnly($array, $options, $depth)
	{
		if ($depth > $options['max_depth']) {
			return "[] // Max depth reached";
		}

		if (empty($array)) {
			return "[]";
		}

		$indent = str_repeat($options['indentation'], $depth);
		$innerIndent = str_repeat($options['indentation'], $depth + 1);
		$lines = [];
		$lines[] = "[";
		foreach ($array as $value) {
			$valueString = self::formatValue($value, $options, $depth + 1);
			$lines[] = $innerIndent . $valueString . ',';
		}

		$lines[] = $indent . "]";
		return implode("\n", $lines);
	}
}
