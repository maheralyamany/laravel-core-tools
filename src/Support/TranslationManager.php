<?php

declare(strict_types=1);

namespace Maher\CoreTools\Support;

use Exception;
use Illuminate\Support\Facades\File;
use RuntimeException;

class TranslationManager
{
	private static array $translationsCache = [];

	/**
	 * التحقق إذا كان المفتاح يبدأ باسم ملف موجود فعليًا مع cache لتحسين الأداء
	 */
	private static array $fileExistenceCache = [];

	/**
	 * دالة لمعالجة الترجمات والتحقق من المفاتيح
	 */
	public static function transWithFallback(string $key, array $replace = [], $locale = null)
	{
		if (!\is_string($key)) {
			// dd($key);
		}

		$originalKey = $key;
		$locale = self::getValidLocale($locale);
		$key = self::getValidTranslationKey($key, $locale);
		$translation = trans($key, $replace, $locale);
		if ($translation === $key) {
			// استخراج اسم الملف من المفتاح
			$file = self::extractFileFromKey($key);
			// إذا كانت الترجمة تعيد نفس المفتاح، فهذا يعني أن الترجمة غير موجودة
			return self::handleMissingTranslation($key, $file, $replace, $locale);
		}

		return $translation;
	}

	/**
	 *  التحقق إذا كان المفتاح يبدأ باسم ملف
	 */
	private static function isValidTranslationKey(string $key, ?string $locale = null)
	{
		//return preg_match('/^([a-zA-Z(_|\-)][a-zA-Z0-9(_|\-)])*\.[a-zA-Z_][a-zA-Z0-9_].*$/', $key);
		// return preg_match('/^(messages|new\-messages|validation|auth|message|pagination|passwords)*\.[a-zA-Z_][a-zA-Z0-9_].*$/', $key);
		$locale = self::getValidLocale($locale);
		// يجب أن يحتوي المفتاح على نقطة
		if (!str_contains($key, '.')) {
			return false;
		}

		// استخراج اسم الملف
		$file = explode('.', $key)[0];
		$path = lang_path(sprintf('%s/%s.php', $locale, $file));
		// استخدام cache لتخزين نتيجة وجود الملف
		if (isset(self::$fileExistenceCache[$path])) {
			return self::$fileExistenceCache[$path];
		}

		$exists = file_exists($path);
		self::$fileExistenceCache[$path] = $exists;
		return $exists;
	}

	/**
	 *  إذا كان المفتاح لا يحتوي على نقطة، نضيف اسم ملف افتراضي
	 * @param string $key
	 */
	private static function getValidTranslationKey($key, ?string $locale = null)
	{
		$key = self::getStandrdTransKey($key);
		if (!self::isValidTranslationKey($key, $locale)) {
			$key = 'messages.' . $key;
		}

		return $key;
	}

	/**
	 * استخراج اسم الملف من المفتاح
	 */
	private static function extractFileFromKey($key)
	{
		return explode('.', $key)[0];
	}

	private static function getValidLocale($locale)
	{
		$locale = $locale ?? getAppLocale();
		return $locale;
	}

	/**
	 * معالجة الترجمة المفقودة
	 */
	private static function handleMissingTranslation($key, $file, array $replace, $locale)
	{
		$locale = self::getValidLocale($locale);
		$defaultTranslation = self::getDefaultTranslation($key, $replace);
		if (\is_numeric($defaultTranslation) || self::IsArabic($defaultTranslation)) {
			return $defaultTranslation;
		}

		$newFile = 'new-' . $file;
		// التحقق إذا كان المفتاح موجود في ملف new-messages
		$newKey = str_replace($file . '.', $newFile . '.', $key);
		$newTranslation = trans($newKey, $replace, $locale);
		if ($newTranslation !== $newKey) {
			// الترجمة موجودة في new-messages
			return $newTranslation;
		}

		// الترجمة غير موجودة في أي ملف، نضيفها إلى new-messages
		if ($locale != 'en')
			self::addTranslationToNewFile($key, $file, $locale, $replace);
		// نعيد المفتاح الأصلي أو قيمة افتراضية
		return $defaultTranslation;
	}

	/**
	 * إضافة الترجمة إلى ملف new-messages
	 */
	private static function addTranslationToNewFile($key, $originalFile, $locale = null, array $replace = [])
	{
		$newFile = 'new-' . $originalFile;
		$locale = self::getValidLocale($locale);
		// استخراج المسار من المفتاح
		$keyPath = explode('.', $key);
		array_shift($keyPath);
		// إزالة اسم الملف
		// تحميل الملف الحالي لـ new-messages
		$newMessagesPath = lang_path($locale . '/' . $newFile . '.php');
		$currentTranslations = self::getTranslationFileContent($newMessagesPath);
		// بناء المصفوفة المتداخلة
		$current = &$currentTranslations;
		foreach ($keyPath as $segment) {
			if (!isset($current[$segment]) || !is_array($current[$segment])) {
				$current[$segment] = [];
			}

			$current = &$current[$segment];
		}

		// إذا كانت القيمة الحالية فارغة، نضيف قيمة افتراضية
		if (empty($current)) {
			$current = self::getDefaultTranslation($key, $replace);
			// حفظ الملف المحدث
			self::saveTranslationsToFile($currentTranslations, $newMessagesPath);
		}
	}

	private static function getTranslationFileContent($path)
	{
		if (!isset(self::$translationsCache[$path])) {
			self::$translationsCache[$path] = file_exists($path) ? File::getRequire($path) : [];
		}

		return self::$translationsCache[$path];
	}

	/**
	 * حفظ الترجمات إلى ملف
	 */
	private static function saveTranslationsToFile($translations, $filePath)
	{
		$phpCode = ArrayToPhpConverter::toPhpString($translations, [
			'max_depth' => 15,
		]);
		// التأكد من وجود المجلد
		$dir = dirname($filePath);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		file_put_contents($filePath, $phpCode, LOCK_EX);
	}

	/**
	 * الحصول على ترجمة افتراضية
	 */
	private static function getDefaultTranslation($key, array $replace = [])
	{
		$keyParts = explode('.', $key);
		$lastPart = end($keyParts);
		$text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $lastPart);
		// camelCase
		// تحويل snake_case أو kebab-case إلى نص مقروء
		return ucfirst(str_replace([
			'_',
			'-',
		], ' ', $text));
	}

	public static function getStandrdTransKey($key)
	{
		$newkey = is_string($key) ? mb_strtolower($key, 'UTF-8') : (string) $key;
		$newkey = trim($newkey);
		$newkey = strx()->replaceEnd('.', '', $newkey);
		return $newkey;
	}

	private static function encodeValueForCompare(string $value): string
	{
		$str = self::encodeValueForTranslation($value);
		$str = strx()->lower(\str_replace(" ", "", \trim($str)));
		return $str;
	}

	public const DOUBLICATED = "<DOUBLICATED>";

	public const ARABIC_PATTERN = '/[\p{Arabic}]/u';

	private static function encodeValueForTranslation(string $content): string
	{
		return preg_replace('/:([a-zA-Z0-9_-]*)/', '<:$1>', $content);
	}

	private static function IsArabic(string $content)
	{
		return \parseBoolean(\preg_match('/[\p{Arabic}]/u', $content));
	}

	private static function updateLocaleArrayKeys(array $array)
	{
		$newarray = [];
		foreach ($array as $key => $value) {
			$newkey = self::getStandrdTransKey($key);
			if (is_array($value)) {
				$value = self::updateLocaleArrayKeys($value);
			} elseif (!\is_string($value)) {
				$value = (string) $value;
			}

			if (\array_key_exists($newkey, $newarray)) {
				$oldValue = $newarray[$newkey];
				$isDiff = false;
				if (\is_string($value) && !\is_string($oldValue) || !\is_string($value) && \is_string($oldValue)) {
					$isDiff = true;
				} elseif (\is_string($value) && \is_string($oldValue)) {
					$isDiff = trim($oldValue) !== trim($value);
				} elseif (\is_array($value) && \is_array($oldValue)) {
					$diff = arr()->arrayDiffAssoc($value, $oldValue, 'key');
					$isDiff = count($diff) > 0;
				}

				//if (self::encodeValueForCompare($oldValue) !== self::encodeValueForCompare($value))
				if ($isDiff) {
					$newkey .= self::DOUBLICATED;
				}
			}

			$newarray[$newkey] = $value;
		}

		ksort($newarray, SORT_REGULAR);
		return $newarray;
	}

	public static function mergeFiles($filePath, ...$files)
	{
		$base = base_path('/');
		$filePath = FileHelper::getFullPathFromRelative($filePath, $base);
		if (!File::exists($filePath)) {
			throw new RuntimeException(sprintf('Language File : %s not exists', $filePath));
		}

		$mergearray = File::getRequire($filePath);
		foreach ($files as $file) {
			$file = FileHelper::getFullPathFromRelative($file, $base);
			$array = File::getRequire($file);
			$mergearray = count($mergearray) > 0 ? arr()->mergeRecursive($mergearray, $array, 'keep_first') : $array;
		}

		ksort($mergearray, SORT_REGULAR);
		return self::saveToFile($mergearray, $filePath);
	}

	public static function mergeDirectoryFiles(string $directory)
	{
		$files = File::allFiles($directory);
		$mergearray = [];
		foreach ($files as $file) {
			$array = File::getRequire($file->getPathname());
			$mergearray = count($mergearray) > 0 ? arr()->mergeRecursive($mergearray, $array) : $array;
		}
		$filePath = $directory . "/merged.php";
		self::saveToFile($mergearray, $filePath);
	}
	public static function copyMessagesFromLanguage(string $from, string $to, string $fromFile = "messages", $toFile = "new-messages", bool $overwrite = false, bool $checkEmpty = true): array
	{
		$toFile = strx()->lower($toFile);
		$fromFile = strx()->lower($fromFile);
		$toLangPath = lang_path("/{$to}/{$toFile}.php");
		$toMessages = FileHelper::getRequire($toLangPath);
		if (count($toMessages) > 0 && !$overwrite)
			return $toMessages;
		if ($checkEmpty && $fromFile != $toFile) {
			if (count(FileHelper::getRequire(lang_path("/{$to}/{$fromFile}.php"))) > 0)
				return $toMessages;
		}
		$fromLangPath = lang_path("/{$from}/{$fromFile}.php");
		$fromMessages = FileHelper::getRequire($fromLangPath);
		if (count($fromMessages) == 0)
			return $toMessages;
		$mergearray = count($toMessages) > 0 ? arr()->mergeRecursive($toMessages, $fromMessages, 'keep_first') : $fromMessages;
		if (self::saveToFile($mergearray, $toLangPath))
			return	$mergearray;
		return $toMessages;
	}
	public static function saveToFile(array $array, string $filePath, array $options = ['max_depth' => 50, 'only_letters' => true,])
	{
		return	ArrayToPhpConverter::saveToFile($array, $filePath, $options, 0777);
	}

	public static function chunkLanguageFile(string $filePath, string $targetDirectory = null, int $size = 220)
	{
		$base = base_path('/');
		if (!\str_starts_with($filePath, $base)) {
			$filePath = $base . "/" . $filePath;
		}

		if (\m_empty($targetDirectory)) {
			$targetDirectory = storage_path('/lang/');
		} elseif (!\str_starts_with($targetDirectory, $base)) {
			$targetDirectory = $base . "/" . $targetDirectory;
		}

		$targetDirectory = FileHelper::getValidPath($targetDirectory);
		$filePath = FileHelper::getValidPath($filePath);
		//dd($targetDirectory,$filePath);
		if (!File::exists($filePath)) {
			throw new RuntimeException(sprintf('Language File : %s not exists', $filePath));
		}

		$array = File::getRequire($filePath);
		if (count($array) === 0) {
			return 'File Is empty';
		}

		$filename = FileHelper::getFilenameWithoutExtension($filePath);
		$extension = FileHelper::getFileExtension($filePath);
		$targetDirectory = sprintf('%s/%s', $targetDirectory, $filename);
		$newarray = arr()->filter($array, fn($v, $k) => !self::IsArabic($v) && !self::IsArabic($k));
		if (count($newarray) < count($array)) {
			self::saveToFile($newarray, $filePath);
			$array = $newarray;
		}

		$chunks = ArrayHelper::chunkAssociative($array, $size);
		//dd($targetDirectory, $chunks);
		$result = [];
		foreach ($chunks as $index => $chunk) {
			$file = \sprintf("%s_%s%s", $targetDirectory, $index, $extension);
			$result[$file] = self::saveToFile($chunk, $file);
		}

		return $result;
	}

	public static function scanAndUpdateLocalesKeys()
	{
		$langPath = lang_path('/');
		$files = glob($langPath . '/*/*.php');
		$updatefiles = [];
		foreach ($files as $file) {
			$array = FileHelper::getRequire($file);
			if (count($array) > 0) {
				$newarray = self::updateLocaleArrayKeys($array);
				$doublicate = collect($newarray)->filter(fn($v, $k) => strx()->endsWith($k, self::DOUBLICATED))->mapWithKeys(fn($v, $k) => [
					strx()->replaceEnd(self::DOUBLICATED, '', $k) => $v,
				])->toArray();
				if (count($doublicate) > 0) {
					$newarray = collect($newarray)->filter(fn($v, $k) => !strx()->endsWith($k, self::DOUBLICATED))->toArray();
				}

				//$diff = arr()->arrayDiffAssoc($newarray, $array, 'key');
				self::saveToFile($newarray, $file);
				if (count($doublicate) > 0) {
					$filename = FileHelper::getFilenameWithoutExtension($file);
					$directory = FileHelper::getFileDirectory($file);
					self::saveToFile($doublicate, sprintf('%s/doublicate-%s.php', $directory, $filename));
					$updatefiles[] = $file;
				}
			}
		}

		return $updatefiles;
	}

	public static function scanAndMergeLangsFiles(string|null $lang = null, $targetFile = "messages", $sourceFile = "new-messages", bool $deleteSource = false)
	{
		$targetFile = strx()->lower($targetFile);
		$sourceFile = strx()->lower($sourceFile);
		$directories = m_empty($lang) ? FileHelper::getDirectories(lang_path('/')) : [lang_path($lang)];
		$updated = [];
		//dd($directories);
		foreach ($directories as $directory) {
			$targetPath = $directory . "/{$targetFile}.php";
			$sourcePath = $directory . "/{$sourceFile}.php";
			if (File::exists($targetPath) && File::exists($sourcePath)) {
				$translatedMessagesArray = FileHelper::getRequire($targetPath);
				$newMessagesArray = FileHelper::getRequire($sourcePath);
				//mergeRecursive
				$mergearray = count($translatedMessagesArray) > 0 ? arr()->mergeRecursive($translatedMessagesArray, $newMessagesArray, 'keep_first') : $newMessagesArray;
				//$diff = arr()->arrayDiffAssoc($newMessagesArray, $translatedMessagesArray, 'key');
				$oldCount = count($newMessagesArray);
				$diffCount = count($mergearray);
				if ($diffCount !== $oldCount) {
					ksort($mergearray, SORT_REGULAR);
					self::saveToFile($mergearray, $targetPath, [
						'ignore_numeric_keys' => true,
						'only_letters' => true,
						'max_depth' => 50,
					]);
					$updated[$targetPath] = [
						'new' => $diffCount,
						'old' => $oldCount,
					];
				}
				if ($deleteSource) {
					FileHelper::deleteFile($sourcePath);
				}
			}
		}
		dd($updated);
		return $updated;
	}
	public static function scanAndUpdateDoublicateKeys(...$directories)
	{
		$directories = m_empty($directories) ? FileHelper::getDirectories(lang_path('/')) : $directories;
		$updated = [];
		//dd($directories);
		foreach ($directories as $directory) {
			$messagesPath = $directory . '/messages.php';
			$newMessagePath = $directory . '/new-messages.php';
			if (File::exists($messagesPath) && File::exists($newMessagePath)) {
				$translatedMessagesArray = File::getRequire($messagesPath);
				$newMessagesArray = File::getRequire($newMessagePath);
				$diff = arr()->arrayDiffAssoc($newMessagesArray, $translatedMessagesArray, 'key');
				$oldCount = count($newMessagesArray);
				$diffCount = count($diff);
				if ($diffCount !== $oldCount) {
					self::saveToFile($diff, $newMessagePath, [
						'ignore_numeric_keys' => true,
						'only_letters' => true,
						'max_depth' => 50,
					]);
					$updated[$newMessagePath] = [
						'new' => $diffCount,
						'old' => $oldCount,
					];
				}
			}
		}
		dd($updated);
		return $updated;
	}

	public static function scanAndUpdateArabicLocalesKeys()
	{
		$langPath = lang_path('/');
		$files = glob($langPath . '/*/*.php');
		$updatefiles = [];
		foreach ($files as $file) {
			$array = File::getRequire($file);
			if (count($array) > 0) {
				$arabicArray = [];
				$newarray = [];
				$numricarray = [];
				foreach ($array as $key => $value) {
					if (is_string($key)) {
						if (self::IsArabic($key)) {
							$arabicArray[$key] = $value;
						} else {
							$newarray[$key] = $value;
						}
					} elseif ($value == $key) {
						$numricarray[$key] = $value;
					} else {
						$newarray[$key] = $value;
					}
				}

				if ($newarray !== []) {
					self::saveToFile($newarray, $file, [
						'ignore_numeric_keys' => false,
						'max_depth' => 50,
					]);
				}

				/*    if (count($arabicArray) > 0) {
                          dd($file, $arabicArray);
                      } */
				if ($arabicArray !== [] || $numricarray !== []) {
					if ($arabicArray !== []) {
						$filename = FileHelper::getFilenameWithoutExtension($file);
						$directory = FileHelper::getFileDirectory($file);
						$removed = [
							'arabic' => $arabicArray,
							'numric' => $numricarray,
						];
						self::saveToFile($removed, sprintf('%s/removed-%s.php', $directory, $filename));
					}

					$updatefiles[$file] = $removed;
				}

				/*  $newarray = self::updateLocaleArrayKeys($array);
                 */
			}
		}

		dd($updatefiles);
		return $updatefiles;
	}

	/**
	 * دالة لفحص وتحديث جميع المفاتيح المفقودة
	 */
	public static function scanAndUpdateMissingTranslations($locale = null)
	{
		$locale = $locale ?? getAppLocale();
		$results = [
			'added' => [],
			'existing' => [],
			'errors' => [],
		];
		// الحصول على جميع ملفات اللغة
		$langPath = lang_path($locale);
		if (!is_dir($langPath)) {
			$results['errors'][] = 'Directory not found: ' . $langPath;
			return $results;
		}

		$files = glob($langPath . '/*.php');
		foreach ($files as $file) {
			$fileName = pathinfo($file, PATHINFO_FILENAME);
			// تخطى ملفات new-*
			if (str_starts_with($fileName, 'new-')) {
				continue;
			}

			try {
				$translations = require $file;
				self::processFileTranslations($translations, $fileName, $locale, $results);
			} catch (Exception $e) {
				$results['errors'][] = sprintf('Error processing file %s: ', $fileName) . $e->getMessage();
			}
		}

		return $results;
	}

	/**
	 * معالجة ترجمات ملف معين
	 */
	private static function processFileTranslations($translations, $fileName, $locale, &$results, $prefix = '')
	{
		foreach ($translations as $key => $value) {
			$currentKey = $prefix ? sprintf('%s.%s', $prefix, $key) : $key;
			$fullKey = sprintf('%s.%s', $fileName, $currentKey);
			if (is_array($value)) {
				self::processFileTranslations($value, $fileName, $locale, $results, $currentKey);
			} else {
				// التحقق من الترجمة
				$translation = trans($fullKey, [], $locale);
				if ($translation === $fullKey) {
					// الترجمة مفقودة، نضيفها إلى new-file
					self::addTranslationToNewFile($fullKey, $fileName, $locale);
					$results['added'][] = $fullKey;
				} else {
					$results['existing'][] = $fullKey;
				}
			}
		}
	}

	/**
	 * دالة لإنشاء تقرير بالمفاتيح المفقودة
	 */
	public static function generateMissingTranslationsReport($locale = null)
	{
		$scanResults = self::scanAndUpdateMissingTranslations($locale);
		$report = [];
		$report[] = "=== Missing Translations Report ===";
		$report[] = "Language: " . ($locale ?? getAppLocale());
		$report[] = "Keys Added: " . count($scanResults['added']);
		$report[] = "Existing Keys: " . count($scanResults['existing']);
		$report[] = "Errors: " . count($scanResults['errors']);
		if (!empty($scanResults['added'])) {
			$report[] = "";
			$report[] = "Added Keys:";
			foreach ($scanResults['added'] as $key) {
				$report[] = "  - " . $key;
			}
		}

		if (!empty($scanResults['errors'])) {
			$report[] = "";
			$report[] = "Errors:";
			foreach ($scanResults['errors'] as $error) {
				$report[] = "  - " . $error;
			}
		}

		return implode("\n", $report);
	}
}
