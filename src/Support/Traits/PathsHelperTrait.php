<?php

declare(strict_types=1);

namespace Maher\CoreTools\Support\Traits;

use LogicException;

use Exception;
use Throwable;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

use Illuminate\Filesystem\Filesystem;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Support\Facades\URL;
use Maher\CoreTools\Core\Descriptor\DescriptorType;
use Maher\CoreTools\Core\Descriptor\ReflectionDescriptor;

trait PathsHelperTrait
{
	public static function extractFromDirectory(string|array $directory, string|array $patterns = '*.php'): iterable|Finder
	{
		if (!class_exists(Finder::class)) {
			throw new LogicException(\sprintf('You cannot use "%s" as the "symfony/finder" package is not installed. Try running "composer require symfony/finder".', static::class));
		}
		return (new Finder())->in($directory)->name($patterns)->ignoreDotFiles(\true)->ignoreVCSIgnored(\true)->notName('.phpstorm.meta.php')->files();
	}

	public static function getRecursiveIteratorIterator(string $directory): RecursiveIteratorIterator
	{
		$fullPath = static::getFullPath($directory);
		return new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS)
		);
	}
	/**
	 * Get all PHP Model files inside app/Models
	 *
	 * @param string|null  $modelsPath  Path to the models directory
	 * @param array<int, string>|null $ignore  Folders or files to ignore
	 * @param array|null $excludModels  models to ignore
	 * @param int|null $depthLimit  Maximum folder depth (null = unlimited)
	 * @return array<int, ReflectionDescriptor>  
	 */
	public static function getAllModelFiles(?string $modelsPath = null, ?array $ignore = ['tests', 'vendor'], array $excludModels = [], ?int $depthLimit = null)
	{
		$excludModels = $excludModels ?? [];
		$modelsPath = $modelsPath ?? app_path('Models');

		return	static::getAllPathReflectionsInfo(
			$modelsPath,
			[DescriptorType::_CLASS],
			select: function (ReflectionDescriptor $row) {
				$className = $row->className;
				$model = new $className();
				if (!is_subclass_of($model, \Illuminate\Database\Eloquent\Model::class)) {
					return null;
				}

				$row->table = $model->getTable();
				return $row;
			},
			excluded: $excludModels,
			ignore: $ignore,
			depthLimit: $depthLimit
		);

	}
	public static function removeCommentsFromContent(string $content, bool $withQuoted = false)
	{
		// Remove PHP single-line and multi-line comments and strings
		// to avoid false matches inside comments or strings
		$contentNoComments  = preg_replace([
			'/\/\/.*$/m',                // remove // comments
			'/#.*$/m',                  // remove # comments
			'/\/\*[\s\S]*?\*\//',       // remove /* */ comments

		], '', $content);
		if ($withQuoted) {
			$contentNoComments  = preg_replace([
				'/(["\']).*?\1/s',          // remove quoted strings
			], '', $contentNoComments);
		}
		return $contentNoComments;
	}
	/**
	 * Summary of getPhpClassesFromContentWithLine
	 * @param string $content
	 * @param array $types
	 * @param bool $onlyHasNameSpace
	 * @return array[]
	 */
	public static function getPhpClassesFromContentWithLine($content, $types, $onlyHasNameSpace = true): array
	{
		$descriptorTypes = collect($types)->mapWithKeys(function ($t, $k) {

			$type = DescriptorType::fromName($t);
			return	[$type->toString() => $type];
		});
		// Step 1: Remove comments and strings to avoid false matches
		$contentNoComments = static::removeCommentsFromContent($content, true);

		$results = [];
		$pushToResults = function (array $row) use (&$results, $descriptorTypes) {
			$type = $descriptorTypes->get($row['type'], DescriptorType::_OTHER);

			if ($type->isValid()) {
				$row['type'] = $type;
				$results[] = $row;
			}
		};

		// Step 2: Detect namespaces (supports both bracketed and unbracketed)
		$namespacePattern = '/namespace\s+([^;\s{]+)\s*(\{)?([\s\S]*?)(?(2)\})/i';
		$namespacePattern = '/namespace\s+([^;\s{]+)\s*(?:\{([\s\S]*?)\}|;)/i';
		if (preg_match_all($namespacePattern, $contentNoComments, $nsMatches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {

			foreach ($nsMatches as $i => $nsMatch) {
				$namespace = trim($nsMatch[1][0]);
				$nsStartOffset = intval($nsMatch[0][1]);
				$nsBody = '';

				if (!empty($nsMatch[2][0])) {
					// Bracketed style: body inside {}
					$nsBody = $nsMatch[2][0];
				} else {
					// Unbracketed style: capture until next namespace or end of file
					$nextNamespaceOffset = isset($nsMatches[$i + 1])
						? $nsMatches[$i + 1][0][1]
						: strlen($contentNoComments);
					$nsBody = substr($contentNoComments, $nsStartOffset, $nextNamespaceOffset - $nsStartOffset);
				}

				// Step 3: Find class-like declarations in this namespace
				// Step 3: Find all class-like declarations in this namespace body
				if (preg_match_all(
					'/\b(class|interface|trait|enum)\s+([A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)\b/i',
					$nsBody,
					$matches,
					PREG_OFFSET_CAPTURE | PREG_SET_ORDER
				)) {

					foreach ($matches as $match) {
						$type = strtolower($match[1][0]);
						$name = $match[2][0];
						// Compute the absolute position in file (namespace start + relative position)
						$absoluteOffset = $nsStartOffset + $match[0][1];
						$lineNumber = substr_count(substr($content, 0, $absoluteOffset), "\n") + 1;
						$pushToResults([
							'type' => $type,
							'shortName' => $name,
							'nameSpace' => $namespace,
							'fullName' => $namespace . '\\' . $name,
							'line' => $lineNumber,
						]);
					}
				}
			}
		} elseif (!$onlyHasNameSpace) {
			// No namespace — search the entire file
			if (preg_match_all(
				'/\b(class|interface|trait|enum)\s+([A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)\b/i',
				$contentNoComments,
				$matches,
				PREG_OFFSET_CAPTURE | PREG_SET_ORDER
			)) {
				foreach ($matches as $match) {
					$type = strtolower($match[1][0]);
					$name = $match[2][0];
					$absoluteOffset = intval($match[0][1]);
					$lineNumber = substr_count(substr($content, 0, $absoluteOffset), "\n") + 1;
					$pushToResults([
						'type' => $type,
						'shortName' => $name,
						'nameSpace' => '',
						'fullName' => $name,
						'line' => $lineNumber,
					]);
				}
			}
		}

		return $results;
	}

	/**
	 * Get all PHP classes files info
	 *
	 * @param string|null  $directory  Path to the php classes directory
	 * @param array<int, DescriptorType|string> $types  the types off class as [DescriptorType::_CLASS,DescriptorType::_TRAIT]
	 * @param (callable(ReflectionDescriptor):ReflectionDescriptor|null)|null $select
	 * @param array|null $excluded  classes to ignore
	 * @param array<int, string>|null $ignore  Folders or files to ignore
	 * @param int|null $depthLimit  Maximum folder depth (null = unlimited)
	 * @return array<int, ReflectionDescriptor> 
	 */
	public static function getAllPathReflectionsInfo(
		?string $directory = null,
		array $types = [DescriptorType::_CLASS],

		?callable $select = null,
		array $excluded = [],
		?array $ignore = ['tests', 'vendor'],
		?int $depthLimit = null
	) {
		$excluded = $excluded ?? [];
		$directory = $directory ?? app_path('/');
		$directory = static::getFullPath($directory);
		if (!is_dir($directory)) {
			return [];
		}

		$hasFilterClasses = \count($excluded) > 0;

		$classesData = static::getAllSubFilesAdvanced(
			$directory,
			//filter: fn($file) => str_ends_with($file->getBasename(), '.php'),
			select: function ($file, $index) use ($hasFilterClasses, $excluded,  $select, $types) {
				if ($hasFilterClasses) {
					$fileName = $file->getFilenameWithoutExtension();

					if (\in_array($fileName, $excluded)) {
						return null;
					}
				}
				$content = $file->getContents();
				/* if (!preg_match('/class\s+(\w+)/', $content, $matches)) {
					return null;
				} */

				$results = static::getPhpClassesFromContentWithLine($content, $types);
				$descriptors = [];
				if (count($results) > 0) {
					if (count($results) > 1)
						dd($results, $content);
					foreach ($results as $attrs) {
						try {

							//$attrs['path'] = $file->getRealPath();
							$descriptor = new ReflectionDescriptor($attrs);
							// --- Select / map callback ---
							if ($select) {
								$mapped = $select($descriptor);
								if (!is_null($mapped)) {
									$descriptors[] = $mapped;
								}
							} else {
								$descriptors[] = $descriptor;
							}
						} catch (\Exception $th) {
							dd($attrs, $th);
							//throw $th;
						}
					}
				}
				if (count($descriptors) > 0)
					return $descriptors;
				return null;
			},
			extension: 'php',
			ignore: $ignore,
			depthLimit: $depthLimit
		);

		//dd($classesData);
		return $classesData;
	}

	/**
	 * Recursively get all files in a directory
	 * (with optional filters, mapping, ignore list, and depth limit)
	 *
	 * @template TKey of array-key
	 * @template TValue
	 * @param string $directory
	 * @param (callable(SplFileInfo):bool)|null $filter
	 * @param (callable(SplFileInfo,int):array<TKey, TValue>|null)|null $select
	 * @param string|array|null $extension
	 * @param array<int, string>|null $ignore
	 * @param int|null $depthLimit
	 * @return array<int, SplFileInfo>|array<TKey, TValue>
	 */
	public static function getAllSubFilesAdvanced(
		string $directory,
		?callable $filter = null,
		?callable $select = null,
		string|array|null $extension = 'php',
		?array $ignore = null,
		?int $depthLimit = null
	) {
		// Normalize extensions
		$extensions = [];
		if (!empty($extension)) {
			$extensions = array_map('strtolower', (array) $extension);
		}

		// Normalize ignore list (case-insensitive)
		$ignoreList = array_map('strtolower', $ignore ?? []);

		$iterator   = static::getRecursiveIteratorIterator($directory);

		$basePath   = rtrim(str_replace('\\', '/', realpath(static::getBasePath())), '/') . '/';
		$baseDepth  = substr_count(trim(str_replace('\\', '/', realpath($directory)), '/'), '/');
		$files      = [];

		foreach ($iterator as $file) {
			if (!$file->isFile()) continue;

			$path         = $file->getPathname();
			$relativePath = static::getRelativePath($path, $basePath);
			$basename     = pathinfo($path, PATHINFO_BASENAME);
			$dirname      = basename($file->getPath());

			// --- Depth limit check ---
			if ($depthLimit !== null) {
				$currentDepth = substr_count(trim(str_replace('\\', '/', $file->getPath()), '/'), '/');
				if (($currentDepth - $baseDepth) >= $depthLimit) continue;
			}

			// --- Ignore check ---
			$lowerPath = strtolower($relativePath);
			$lowerBase = strtolower($basename);
			$lowerDir  = strtolower($dirname);

			$shouldIgnore = false;
			foreach ($ignoreList as $ignoreItem) {
				if (
					str_contains($lowerPath, $ignoreItem) || // partial path
					$lowerBase === $ignoreItem ||            // exact file
					$lowerDir === $ignoreItem                // exact folder
				) {
					$shouldIgnore = true;
					break;
				}
			}
			if ($shouldIgnore) continue;

			// --- Extension check ---
			if (!empty($extensions) && !\in_array(strtolower($file->getExtension()), $extensions, true)) continue;

			$newFile = new SplFileInfo($path, $relativePath, $basename);

			// --- Filter callback ---
			if ($filter && !$filter($newFile)) continue;

			// --- Select / map callback ---
			if ($select) {
				$mapped = $select($newFile, count($files));
				if (!m_empty($mapped)) {
					foreach ($mapped as $key => $value) {
						if (\is_string($key))
							$files[$key] = $value;
						else
							$files[] = $value;
					}
				}
			} else {
				$files[] = $newFile;
			}
		}

		return $files;
	}
	/**
	 * Recursively get all files in a directory (optionally filtered and mapped)
	 *
	 * @template TKey of array-key
	 * @template TValue
	 * @param string $directory  Base directory path
	 * @param (callable(\Symfony\Component\Finder\SplFileInfo):bool)|null $filter
	 * @param (callable(\Symfony\Component\Finder\SplFileInfo,int):array<TKey, TValue>|null)|null $select
	 * @param string|array|null $extension  Allowed extension(s), e.g. 'php' or ['php', 'json']
	 * @return array<int, \Symfony\Component\Finder\SplFileInfo>|array<TKey, TValue>
	 */
	public static function getAllSubFiles(
		string $directory,
		?callable $filter = null,
		?callable $select = null,
		string|array|null $extension = 'php'
	) {

		return	static::getAllSubFilesAdvanced($directory, $filter, $select, $extension);
	}

	/**
	 * Summary of gtetTopLevelFiles
	 * @param (callable(\Symfony\Component\Finder\SplFileInfo):bool)|null $callback
	 * @return \Symfony\Component\Finder\SplFileInfo[]
	 */
	public static function getTopLevelFiles(string $directory, $callback = null)
	{
		$files = new Filesystem();
		$directories = $files->directories($directory);
		$relativeDirs = count($directories) > 0 ? collect($directories)->mapWithKeys(function ($dir, $k) use ($directory) {
			return [
				$k => static::getRelativeDirectory($dir, $directory),
			];
		})->toArray() : [];
		$files = collect((new Finder())->in($directory)->ignoreDotFiles(false)->exclude($relativeDirs)->files());
		if ($callback) {
			$files = $files->filter($callback);
		}

		return $files->toArray();
	}


	public static function getDirectorySubFilesWithGroupByDirectory(string $directory, string|array $patterns = '*.php')
	{
		$directories = static::getSubDirectories($directory);
		$files = collect(static::extractFromDirectory($directories, $patterns))->mapToGroups(function (\Symfony\Component\Finder\SplFileInfo $file, $k) {
			$dire = dirname($file->getPathname());
			return [
				$dire => $file,
			];
		})->mapWithKeys(fn($files, $k) => [
			$k => $files->all(),
		])->all();
		return $files;
	}

	public static function getSubDirectories(string $directory)
	{
		$directories = [];
		$directories = static::getAllSubDirectories($directory, $directories);
		return $directories;
	}

	public static function getDirectories(string $directory)
	{
		$f = new Filesystem();
		$directories = $f->directories($directory);
		return $directories;
	}

	private static function getAllSubDirectories(string $directory, array &$directories)
	{
		$f = new Filesystem();
		$sdirectories = $f->directories($directory);
		$directories[] = $directory;
		foreach ($sdirectories as $dir) {
			// $directories[] = $dir;
			$directories = static::getAllSubDirectories($dir, $directories);
			/*  if (count($ssdirectories) > 0)
                $directories = arr()->merge($directories, $ssdirectories); */
		}

		return $directories;
	}

	public static function normalizeRelativePath(string $path): string
	{
		//return (new WhitespacePathNormalizer())->normalizePath($path);
		// إزالة المسافات والاقتباسات الزائدة
		$path = trim($path, " \t\n\r\0\x0B\"'");
		// استبدال الباك سلاش إلى سلاش أمامي
		$path = str_replace('\\', '/', $path);
		// إزالة السلاشات المتكررة بدون regex
		while (strpos($path, '//') !== false && !str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
			$path = str_replace('//', '/', $path);
		}
		return $path;
	}
	public static function normalizePathOrUrl(string $path): string
	{
		// --- تحديد إن كان URL ---
		$isUrl   = static::isUrl($path);
		$http   = $isUrl ? static::getUrlProtocol($path) : null;
		if ($isUrl) {
			$path = substr($path, strlen($http) + 3); // إزالة http:// أو https:// بسرعة
		}
		// --- إزالة المسافات والاقتباسات فقط مرة واحدة ---
		$path = trim($path, " \t\n\r\0\x0B\"'");
		// --- توحيد جميع أنواع الباك سلاش إلى سلاش (أسرع من regex) ---
		if (strpos($path, '\\') !== false) {
			$path = str_replace('\\', '/', $path);
		}
		// --- إزالة التكرارات // بدون while (باستخدام Regex خفيف وسريع) ---
		// ملاحظة: هذا regex سريع جداً لأنه بسيط جداً:
		$path = preg_replace('#/+#', '/', $path);
		// --- إزالة السلاش الأول ---
		$path = ltrim($path, '/');
		// --- إعادة إضافة البروتوكول ---
		return $isUrl ? ($http . '://' . $path) : $path;
	}
	public static function getUrlProtocol(string $value): ?string
	{
		$value = trim($value);

		// 1) بروتوكول في بداية النص (الأسرع)
		if (preg_match('#^([a-z][a-z0-9+\-.]*):\/\/#i', $value, $m)) {
			return strtolower($m[1]);
		}

		// 2) URL بدون بروتوكول مثل: www.example.com
		if (preg_match('#^[a-z0-9\-]+(\.[a-z0-9\-]+)+#i', $value)) {
			return 'http'; // بروتوكول افتراضي
		}

		// 3) localhost بدون بروتوكول
		if (preg_match('#^(localhost)(:\d+)?(/.*)?$#i', $value)) {
			return 'http';
		}

		// 4) IPv4 بدون بروتوكول
		if (preg_match('#^(\d{1,3}\.){3}\d{1,3}#', $value)) {
			return 'http';
		}

		return null;
	}
	public static function parseUrlParts(string $value): array
	{
		$value = trim($value);
		$protocol = static::getUrlProtocol($value);

		// إن لم يكن URL حقيقي
		if (!$protocol) {
			return [
				'protocol' => null,
				'host'     => null,
				'port'     => null,
				'path'     => $value,
			];
		}

		// إذا لا يوجد بروتوكول صريح، أضف واحد افتراضياً لتحليل صحيح
		if (!str_contains($value, '://')) {
			$value = $protocol . '://' . $value;
		}

		$parts = parse_url($value);

		return [
			'protocol' => $parts['scheme'] ?? null,
			'host'     => $parts['host'] ?? null,
			'port'     => $parts['port'] ?? null,
			'path'     => ($parts['path'] ?? '/') .
				(isset($parts['query']) ? '?' . $parts['query'] : ''),
		];
	}
	public static function detectResourceType(string $value): string
	{
		if (static::getUrlProtocol($value)) {
			return 'url';
		}

		return 'path';
	}
	public static function isPath(string $value): bool
	{
		return !static::isUrl($value);
	}
	public static function isUrl(string $value): bool
	{
		$value = trim($value);

		// 1) فحص سريع جدًا للبروتوكولات المعروفة
		$lower = strtolower($value);
		if (
			str_starts_with($lower, 'http://') ||
			str_starts_with($lower, 'https://') ||
			str_starts_with($lower, 'ftp://') ||
			str_starts_with($lower, 's3://') ||
			str_starts_with($lower, 'file://') ||
			str_starts_with($lower, 'ws://') ||
			str_starts_with($lower, 'wss://')
		) {
			return true;
		}

		// 2) www.example.com OR example.com (بدون بروتوكول)
		if (preg_match('#^[a-z0-9\-]+(\.[a-z0-9\-]+)+(/.*)?$#i', $value)) {
			return true;
		}

		// 3) دعم localhost
		if (preg_match('#^(localhost)(:\d+)?(/.*)?$#i', $value)) {
			return true;
		}

		// 4) دعم IPv4
		if (preg_match('#^(\d{1,3}\.){3}\d{1,3}(:\d+)?(/.*)?$#', $value)) {
			return true;
		}

		// 5) دعم IPv6 بين أقواس []
		if (preg_match('#^\[[0-9a-f:\.]+\](:\d+)?(/.*)?$#i', $value)) {
			return true;
		}

		return false;
	}

	public static function getValidUrl(string $path, $base_url = null): string
	{
		//$path = static::normalizePathOrUrl($path);
		$base_path = static::getBasePath();

		$base_url = static::getBaseUrl($base_url);


		$url = str_replace($base_path, $base_url, str_replace("/", "\\", $path));
		// dd($base_path, $base_url, $url, $path);
		return str_replace(["\\"], "/", $url);
	}
	protected static $basePath;
	protected static $baseUrl;
	public static function getBasePath($base_path = null): string
	{
		if (is_null(static::$basePath)) {
			static::$basePath = static::normalizePathOrUrl(base_path());
		}
		$base_path = $base_path ? static::normalizePathOrUrl($base_path) : static::$basePath;
		return $base_path;
	}
	public static function getBaseUrl($base_url = null): string
	{
		if (is_null(static::$basePath)) {
			static::$baseUrl = static::normalizePathOrUrl(url('/'));
		}
		$base_url = $base_url ? static::normalizePathOrUrl($base_url) : static::$baseUrl;
		return $base_url;
	}
	public static function parsePathToValidUrl(string $path, $base_url = null, $base_path = null): string
	{
		$path = static::normalizePathOrUrl($path);
		$base_path = static::getBasePath($base_path);
		$base_url = static::getBaseUrl($base_url);
		// dd($base_path, $base_url, $url, $path);
		return str_replace($base_path, $base_url, $path);
	}

	public static function parseUrlToRelativePath(string $url, $base_url = null): string
	{
		$base_url = static::getBaseUrl($base_url);
		$path = str_replace($base_url, '', $url);
		$path = str_replace("\\", "/", $path);
		$path = static::normalizePathOrUrl($path);
		return $path;
	}

	public static function getRelativePath(string $path, ?string $basePath = null): string
	{
		$basePath = static::getBasePath($basePath);
		// نحول جميع الفواصل إلى شكل موحد (forward slash)
		$path = static::normalizePathOrUrl($path);
		$basePath = rtrim(static::normalizePathOrUrl($basePath), '/');
		// إذا لم يبدأ المسار بالمسار الأساسي → نعيد المسار كما هو
		if (!str_starts_with($path, $basePath)) {
			return $path;
		}
		// نحذف الجزء الأساسي + الشرطة المائلة
		$relative = ltrim(substr($path, \strlen($basePath)), '/');
		return $relative;
	}

	public static function getValidPath(string $path): string
	{
		$path = static::normalizePathOrUrl($path);
		//$path =  str_replace(["\\"],  "/", realpath($path));
		/* $path = str_replace(["\\", "//"], "/", $path);
         		return  $path; */
		return $path;
	}
	public static function getSubPath(string $path): string
	{
		$base_path =  static::getBasePath();
		$subpath = str_replace($base_path, "", str_replace("/", "\\", $path));
		$subpath = str_replace(["\\"], "/", $subpath);
		//dd($base_path, $subpath, $path);
		return $subpath;
	}
	public static function getSplFileInfo(string $path, ?string $basePath = null): \Symfony\Component\Finder\SplFileInfo
	{
		//$fileInfo = new \Nette\Utils\FileInfo($path);

		$relativePath = static::getRelativePath($path, $basePath);
		$info = pathinfo($path);
		$newFile = new \Symfony\Component\Finder\SplFileInfo($path, $relativePath, $info['basename']);
		// $filesList[] =  $file;
		return $newFile;
	}

	public static function isValidFilePath(string $path): bool
	{
		if (m_empty($path)) {
			return false;
		}

		return static::exists($path) && File::isFile($path);
	}

	public static function deleteFile(string $path): bool
	{
		if (static::isDirectory($path)) {
			collect(File::files($path))->each(function (\Symfony\Component\Finder\SplFileInfo $file): void {
				File::delete($file->getRealPath());
			});
		} elseif (static::exists($path)) {
			File::delete($path);
		}
		return !static::exists($path);
	}

	public static function deleteSubDirectories(string $directory): void
	{
		try {
			if (static::isDirectory($directory)) {
				$files = new Filesystem();
				$directories = $files->directories($directory);
				foreach ($directories as $directory) {
					$files->deleteDirectory($directory);
				}
			}
		} catch (Throwable $throwable) {
			report($throwable);
		}
	}

	public static function deleteDirectory(string $directory): void
	{
		if (static::isDirectory($directory)) {
			File::deleteDirectory($directory);
		}
	}

	public static function getRelativeDirectory($directory, $baseDirectory = null): string
	{
		$baseDirectory = static::getBasePath($baseDirectory);


		if (!str_ends_with((string) $baseDirectory, "/")) {
			$baseDirectory .= "/";
		}

		$baseDirectory = static::normalizePathOrUrl($baseDirectory);
		$directory = static::normalizePathOrUrl($directory);
		return str_replace($baseDirectory, '', $directory);
	}

	public static function getFullPathFromRelative($relativePath, $baseDirectory = null): string
	{
		$relativePath = static::normalizePathOrUrl($relativePath);
		$baseDirectory = static::getBasePath($baseDirectory);


		if (!\str_starts_with($relativePath, $baseDirectory)) {
			$relativePath = $baseDirectory . "/" . $relativePath;
		}

		return static::normalizePathOrUrl($relativePath);
	}
	/**
	 * Get the full absolute path or public URL from a relative path.
	 *
	 * - Keeps full paths or URLs unchanged
	 * - Resolves relative paths to full filesystem path
	 * - Converts public or storage files to public URLs if they exist
	 *
	 * @param  string  $relativePath
	 * @param  bool    $preferUrl  Whether to return public URL if available
	 * @return string
	 */
	public static function getFullPath(string $relativePath, bool $preferUrl = false): string
	{
		$relativePath = trim($relativePath);
		$relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

		// 1️⃣ Skip if already full path or URL
		if (
			preg_match('/^[A-Z]:\\\\/i', $relativePath) ||   // Windows drive
			preg_match('/^\/[a-zA-Z0-9_\-]+/i', $relativePath) || // Absolute Unix path
			preg_match('/^https?:\/\//i', $relativePath)  ||   // URL
			preg_match('/^http?:\/\//i', $relativePath)
		) {
			return $relativePath;
		}

		// 2️⃣ Build full absolute path
		$fullPath = base_path($relativePath);

		// 3️⃣ Try to resolve to real filesystem path
		$resolvedPath = realpath($fullPath) ?: $fullPath;

		// 4️⃣ Optionally return public URL if applicable
		if ($preferUrl) {
			// Case A: Path is under "public"
			$publicPath = public_path();
			if (str_starts_with($resolvedPath, $publicPath) && file_exists($resolvedPath)) {
				$relativeToPublic = str_replace($publicPath . DIRECTORY_SEPARATOR, '', $resolvedPath);
				return URL::to(str_replace(DIRECTORY_SEPARATOR, '/', $relativeToPublic));
			}

			// Case B: Path is under "storage/app/public"
			$storagePath = storage_path('app/public');
			if (str_starts_with($resolvedPath, $storagePath) && file_exists($resolvedPath)) {
				$relativeToStorage = str_replace($storagePath . DIRECTORY_SEPARATOR, '', $resolvedPath);
				return URL::to('storage/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativeToStorage));
			}
		}

		// 5️⃣ Fallback: return full absolute path
		return $resolvedPath;
	}

	/**
	 * Determine if the given path is a file.
	 *
	 * @param  string  $file
	 */
	public static function isFile($file): bool
	{
		return is_file($file);
	}

	public static function isDirectory(string $directory): bool
	{
		return is_dir($directory);
	}

	public static function exists(string $path): bool
	{
		return file_exists($path);
	}

	public static function getFileExtension(string $path): string
	{
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		if ($extension && !str_starts_with($extension, ".")) {
			$extension = "." . $extension;
		}

		return $extension;
	}

	public static function getFilenameWithoutExtension(string $path): string
	{
		$extension = static::getFileExtension($path);
		$name = pathinfo($path, PATHINFO_FILENAME);
		//$fileName = Str::replaceEnd(".", "", $fileName);
		return \str_replace($extension, "", $name);
	}

	public static function getFileDirectory(string $filename): string
	{
		$directory = dirname($filename);
		// $directory = pathinfo($filename, PATHINFO_DIRNAME);
		return $directory;
	}

	public static function getPathDirectory(string $path): string
	{
		$directory = $path;
		if (is_file($path) || pathinfo($path, PATHINFO_EXTENSION)) {
			$directory = dirname($path);
		}

		return $directory;
	}

	public static function makeDirectory(string $path, int $mode = 0755, bool $recursive = true): bool
	{
		if (!static::isDirectory($path)) {
			return File::makeDirectory($path, $mode, $recursive);
		}

		return true;
	}

	public static function makeDirectoryIFNotExists(string $path, int $mode = 0755, bool $recursive = true): bool
	{
		$directory = static::getPathDirectory($path);
		if (!static::isDirectory($directory)) {
			return static::makeDirectory($directory, $mode, $recursive);
		}

		return true;
	}

	public static function ensureFileDirectoryExists(string $path, int $mode = 0755, bool $recursive = true): bool
	{
		$directory = static::getPathDirectory($path);
		if (!static::isDirectory($directory)) {
			return static::makeDirectory($directory, $mode, $recursive);
		}

		return true;
	}

	public static function ensureDirectoryExists(string $base_path, string $subpath): string
	{
		if (!str_ends_with($base_path, "/"))
			$base_path .= "/";
		try {
			$path = $base_path .  $subpath;
			if (!static::isDirectory($path)) {
				/* $paths = explode("/", $subpath);
				if (count($paths) > 1) {
					$path = $base_path;
					foreach ($paths as $p) {
						$path = $path . "/" . $p;
						File::ensureDirectoryExists($path);
					}
				} else
					File::ensureDirectoryExists($path); */
				static::makeDirectory($path);
			}
		} catch (\Throwable $th) {
			report($th);
		}
		return $base_path . $subpath;
	}
}
