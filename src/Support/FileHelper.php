<?php

declare(strict_types=1);

namespace Maher\CoreTools\Support;

use Exception;
use Throwable;
use Illuminate\Support\Facades\File;


use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Maher\CoreTools\Support\Traits\PathsHelperTrait;

class FileHelper
{
	use PathsHelperTrait;
	public static function addDirectoryToPath(string $path,  string|null $directory = null): string
	{

		if (!m_empty($directory)) {
			$path = ((strx()->startsWith($path, $directory)) ? $path : ($directory . '/' . $path));
		}
		return $path;
	}

	public static function removeDirectoryFromPath(string|null $path,  string|null $directory = null): string|null
	{
		if (!m_empty($directory) && !m_empty($path)) {
			$path = strx()->replaceStart($directory, "", $path);
		}
		return $path;
	}
	public static function deleteStoragePublicFiles(string|array|null $paths, string|null $disk = null, string|null $directory = null): bool
	{
		try {
			// Delete old files if any
			if (!m_empty($paths)) {
				$disk = $disk ?: 'local';
				foreach ((array) $paths as $path) {
					$path = FileHelper::addDirectoryToPath($path, $directory);



					//$oldFiles = arr()->mapWithKeys($oldImages, fn($v, $k) => [$k => ((strx()->startsWith($v, $dir)) ? $v :	$dir . $v)]);
					if (Storage::disk($disk)->exists($path)) {
						Storage::disk($disk)->delete($path);
					}
				}
			}
			return true;
		} catch (Exception $exception) {
			//report($th);
			//throw $th;
		}
		return false;
	}




	/**
	 * Check if remote file (image or any file) exists with caching
	 *
	 * @param string $url
	 * @param bool $forceRefresh If true, bypasses cache and fetches new data
	 * @param int $cacheMinutes Cache duration in minutes (default: 60)
	 * @return array{exists:bool,status: int|null,content_type: string|null,is_image:bool}
	 */
	public static function fileExistsRemote(string $url, bool $forceRefresh = false, int $cacheMinutes = 60): array
	{
		$cacheKey = 'file_exists_' . md5($url);
		// 🧠 إذا كان المستخدم يريد تجاهل الكاش
		if ($forceRefresh) {
			Cache::forget($cacheKey);
		}
		return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($url) {
			try {
				// طلب سريع جدًا (بايت واحد فقط)
				$response = Http::withHeaders([
					'Range' => 'bytes=0-0',
				])->timeout(5)->get($url);
				$status = $response->status();
				$contentType = $response->header('Content-Type');
				// نعتبر الملف موجود إذا كانت الاستجابة ناجحة ومعقولة
				$exists = in_array($status, [200, 206, 302, 304]) && (string) $response->body() !== '';
				// نتحقق هل هو صورة أم لا بناءً على Content-Type
				$isImage = $contentType && str_starts_with($contentType, 'image/');
				return [
					'exists' => $exists,
					'status' => $status,
					'content_type' => $contentType,
					'is_image' => $isImage,
				];
			} catch (Exception $exception) {
				return [
					'exists' => false,
					'status' => null,
					'content_type' => null,
					'is_image' => false,
				];
			}
		});
	}
	/**
	 * Check if a remote file exists (any type), with caching, size, and force refresh.
	 *
	 * @param string $url
	 * @param bool $forceRefresh If true, bypasses cache and fetches new data
	 * @param int $cacheMinutes Cache duration in minutes (default: 60)
	 * @return  array{exists:bool,status: int|null,content_type: string|null,content_length: int|null,is_image:bool}
	 */
	public static function fileExistsRemoteWithContent(string $url, bool $forceRefresh = false, int $cacheMinutes = 60)
	{
		$cacheKey = 'file_exists_' . md5($url);
		// 🧠 إذا كان المستخدم يريد تجاهل الكاش
		if ($forceRefresh) {
			Cache::forget($cacheKey);
		}
		// ⚡ نحاول الحصول من الكاش أو تنفيذ الفحص الجديد
		return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($url) {
			try {
				// نجرب أولاً طلب HEAD لتقليل البيانات
				$response = Http::timeout(5)->head($url);
				$status = $response->status();
				$contentType = $response->header('Content-Type');
				$contentLength = $response->header('Content-Length');
				// إذا لم يُرجع السيرفر معلومات كافية، نعمل GET صغير جدًا
				if (!$contentType || !$contentLength || !in_array($status, [200, 206])) {
					$response = Http::withHeaders([
						'Range' => 'bytes=0-0',
					])->timeout(5)->get($url);
					$status = $response->status();
					$contentType = $contentType ?? $response->header('Content-Type');
					$contentLength = $contentLength ?? $response->header('Content-Length');
				}
				$exists = in_array($status, [200, 206, 302, 304]);
				$isImage = $contentType && str_starts_with(strtolower($contentType), 'image/');
				return [
					'exists' => $exists,
					'status' => $status,
					'content_type' => $contentType,
					'content_length' => $contentLength ? (int) $contentLength : null,
					'is_image' => $isImage,
				];
			} catch (Exception $exception) {
				return [
					'exists' => false,
					'status' => null,
					'content_type' => null,
					'content_length' => null,
					'is_image' => false,
				];
			}
		});
	}

	public static function getStoragePublicRelativePath(string $path): string
	{
		try {
			$path = self::parseUrlToRelativePath($path);
			$storage = storage_path('/app');
			$relpath = self::getRelativePath($path, $storage);
			$path = str_replace("storage/app", "", $relpath);
			return $path;
			//return Storage::disk('public')->fileExists($path);
		} catch (Exception $exception) {
			//report($th);
			//throw $th;
		}
		return $path;
	}
	public static function getStoragePublicFullPath(string $path): string
	{
		try {
			$storage = self::getValidPath(storage_path('/app'));
			if (\str_starts_with($path, $storage)) {
				return $path;
			}
			if (!\str_starts_with($path, 'public/')) {
				$storage = self::getValidPath($storage . '/public');
			}
			$full_path = self::getValidPath($storage . '/' . $path);
			return $full_path;
		} catch (Exception $exception) {
			report($exception);
			//throw $th;
		}
		return $path;
	}

	public static function deleteStoragePublicFile(string $path): bool
	{
		try {
			$full_path = self::getStoragePublicFullPath($path);
			if (self::deleteFile($full_path)) {
				return true;
			}
		} catch (Exception $exception) {
			report($exception);
			//throw $th;
		}
		try {
			$path = self::getStoragePublicRelativePath($path);
			if (Storage::exists($path)) {
				return Storage::delete($path);
			}
			return true;
		} catch (Exception $exception) {
			report($exception);
			//throw $th;
		}
		return false;
	}

	public static function storagePublicPathExists(string $path): bool
	{
		try {

			$path = self::getStoragePublicRelativePath($path);
			if (!strx()->startsWith($path, 'public/'))
				$path = 'public/' . $path;

			return Storage::exists($path);
			//return Storage::disk('public')->fileExists($path);
		} catch (Exception $exception) {
			//report($th);
			//throw $th;
		}

		return false;
	}

	public static function storagePublicPathExists1(string $path): bool
	{
		try {
			$path = str_replace("\\", "/", $path);
			$base_url = static::getBaseUrl();
			$path = trim(str_replace($base_url, "", $path));
			$storagePath = "storage/app";
			$path = static::getValidPath($path);
			$base = static::getBasePath();
			//dd($path,\str_starts_with($path, $storagePath), \str_starts_with($path, $base));
			if (!\str_starts_with($path, $storagePath) && !\str_starts_with($path, $base)) {
				$path = $storagePath . "/" . $path;
			}

			if (!\str_starts_with($path, $base)) {
				$path = $base . "/" . $path;
			}

			$path = static::getValidPath($path);
			//$path = str_replace("storage/app/public/", "", $path);
			if (is_file($path)) {
				return \file_exists($path);
			}

			return \is_dir($path);
			//return Storage::disk('public')->fileExists($path);
		} catch (Exception $exception) {
			//report($th);
			//throw $th;
		}

		return false;
	}



	/**
	 * Write the contents of a file.
	 *
	 * @param  string  $path
	 * @param  string  $contents
	 * @param  bool  $lock
	 * @return bool
	 */
	public static function put($path, $contents, $lock = false)
	{

		static::ensureFileDirectoryExists($path);
		/* if (static::exists($path)) {
         			File::delete($path);
         		} */
		$res = File::put($path, $contents, $lock);
		return is_numeric($res) ? true : $res;
	}

	/**
	 * Get the contents of a file.
	 *
	 * @param  string  $path
	 * @param  string|null  $default
	 * @return string
	 */
	public static function get($path, $default = null)
	{
		if (static::isFile($path)) {
			$files = new Filesystem();
			return $files->get($path);
		}

		return $default;
	}

	/**
	 * Get the contents of a file as decoded JSON.
	 *
	 * @param  string  $path
	 * @param  array  $default
	 * @param  int  $flags
	 * @return array
	 */
	public static function json($path, $default = [], $flags = 0)
	{
		$contents = static::get($path);
		if (!m_empty($contents)) {
			return json_decode($contents, true, 512, $flags);
		}

		return $default;
	}
	public static function getRequire($path, $default = [])
	{
		if (self::isFile($path) && self::exists($path)) {
			try {
				return File::getRequire($path);
			} catch (\Throwable $th) {
				//throw $th;
			}
		}
		return $default;
	}
}
