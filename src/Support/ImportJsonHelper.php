<?php

declare(strict_types=1);

namespace Maher\CoreTools\Support;

use Maher\CoreTools\Models\Brand;
use Maher\CoreTools\Models\Category;
use Maher\CoreTools\Models\Product;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class ImportJsonHelper
{

	//
	public static function updateViewLayoutApp(string $directory)
	{
		$directories = FileHelper::getSubDirectories($directory);
		$search = "@extends(";
		$replace = "@extends('web-views.layouts.front-end.app-list')";
		$files = collect(FileHelper::extractFromDirectory($directories))->filter(function (SplFileInfo $file, $k) use ($search, $replace) {
			$content = $file->getContents();
			//
			return (str_contains($content, '->links()') || str_contains($content, '<x-table-pagination')) && (!str_contains($content, "@extends('layouts.back-end.app-seller-list')") && !str_contains($content, "@extends('layouts.back-end.app-list')") && !str_contains($content, "@extends('web-views.layouts.front-end.app-list')")) && str_contains($content, $search);
		})->all();
		dd(count($files), $files);
		$updated = [];
		foreach ($files as $file) {
			$content = $file->getContents();
			$newcontent = \str_replace($search, $replace, $content);
			if ($newcontent !== $content) {
				File::replace($file->getPathname(), $newcontent);
				$updated[] = $file->getPathname();
			}

			//dd($file->getPathname(),$newcontent, $content);
		}

		dd(count($updated), $updated);
		//(new Finder())->files()->name('*.php')->in($path)
	}

	public static function updateViewFileName(string $directory)
	{
		$directories = FileHelper::getSubDirectories($directory);
		//dd($directories);
		// $pattern = '/VIEW_FILE_NAMES\[[\'"](.*?)[\'"]\]/';
		$pattern = '/view\(getViewFileName\([\'"](.*?)[\'"]\)/';
		$files = collect(FileHelper::extractFromDirectory($directories))->filter(function (SplFileInfo $file, $k) use ($pattern) {
			$content = $file->getContents();
			if ($file->getFilenameWithoutExtension() === 'ImportJsonHelper') {
				return false;
			}

			/*  if ($file->getFilenameWithoutExtension() === 'UserProfileController') {
                    preg_match('/VIEW_FILE_NAMES\[[\'"](.\w+)[\'"]\]/', $content, $matches);
                    dd(
                        preg_match($pattern, $content),
                        $matches,
                        preg_match('/^VIEW_FILE_NAMES?\[+(\w+)\]/', $content),
                        str_contains($content, 'view(VIEW_FILE_NAMES'),
                        $k
                    );
                } */
			return preg_match($pattern, $content);
		})->all();
		// dd(count($files), $files);
		foreach ($files as $file) {
			$content = $file->getContents();
			$newcontent = preg_replace_callback($pattern, function ($matches) {
				$all = $matches[0];
				$key = $matches[1];
				//if ($view === null)
				// dd($all, $key);
				return sprintf("theme_view('%s'", $key);
			}, $content);
			if ($newcontent !== $content) {
				File::replace($file->getPathname(), $newcontent);
			}

			//dd($file->getPathname(),$newcontent, $content);
		}

		dd(count($files), $files);
		//(new Finder())->files()->name('*.php')->in($path)
	}

	public static function updateViewFileName1(string $directory)
	{
		$directories = FileHelper::getSubDirectories($directory);
		//dd($directories);
		$themesfile = File::getRequire(base_path('resources/themes/default/file_names.php'));
		$pattern = '/VIEW_FILE_NAMES\[[\'"](.*?)[\'"]\]/';
		$files = collect(FileHelper::extractFromDirectory($directories))->filter(function (SplFileInfo $file, $k) use ($pattern) {
			$content = $file->getContents();
			//'/^[a-zA-Z_][a-zA-Z0-9_]*$/'
			if ($file->getFilenameWithoutExtension() === 'ImportJsonHelper') {
				return false;
			}

			/*  if ($file->getFilenameWithoutExtension() === 'UserProfileController') {
                    preg_match('/VIEW_FILE_NAMES\[[\'"](.\w+)[\'"]\]/', $content, $matches);
                    dd(
                        preg_match($pattern, $content),
                        $matches,
                        preg_match('/^VIEW_FILE_NAMES?\[+(\w+)\]/', $content),
                        str_contains($content, 'view(VIEW_FILE_NAMES'),
                        $k
                    );
                } */
			return preg_match($pattern, $content) || str_contains($content, 'view(VIEW_FILE_NAMES');
		})->all();
		// dd(count($files), $files);
		foreach ($files as $file) {
			$content = $file->getContents();
			$newcontent = preg_replace_callback($pattern, function ($matches) use ($themesfile) {
				$all = $matches[0];
				$key = $matches[1];
				$view = $themesfile[$key] ?? $key;
				if ($view === null) {
					dd($all, $key, $view);
				}

				return sprintf("getViewFileName('%s')", $view);
			}, $content);
			if ($newcontent !== $content) {
				File::replace($file->getPathname(), $newcontent);
			}

			//dd($file->getPathname(),$newcontent, $content);
		}

		dd(count($files), $files);
		//(new Finder())->files()->name('*.php')->in($path)
	}

	public static function updateProductImages()
	{
		dd(Product::query()->get()->mapWithKeys(function (Product $p, $k) {
			//[{"image_name":"2025-10-03-68dfe6c7249f8.webp","storage":"public"}]
			$image_name = sprintf("%s.jpg", $p->id);
			$images = json_encode([['image_name' => $image_name, 'storage' => "public"]]);
			$color_image = "[]";
			$thumbnail = $image_name;
			if ($p->update([
				'images' => $images,
				'thumbnail' => $thumbnail,
				'color_image' => $color_image,
			])) {
				$p->images = $images;
				$p->color_image = $color_image;
				$p->thumbnail = $thumbnail;
			}
			return [$p->id => [
				'name' => $p->name,
				'id' => $p->id,
				'images' => $p->images,
				'thumbnail' => $p->thumbnail,
				'meta_image' => $p->meta_image,
				'color_image' => $p->color_image,
			]];
		})->toArray());
	}
	public static function updateJson($jsonPath)
	{
		$brands = collect(Brand::select('id', 'name')->get()->mapWithKeys(function ($v, $k) {
			return [
				$k => [
					'id' => $v['id'],
					'name' => $v['name'],
				],
			];
		}));
		$subCategories = Category::where('parent_id', '>', 0)->select('name', 'parent_id', 'id')->get()->mapWithKeys(function ($v, $k) {
			/* return [$v['name'] . '_' . $v['id'] => [
                   'id' => $v['id'],
                   'name' => $v['name'],
                   'parent_id' => $v['parent_id'],
               ]]; */
			return [
				$v['name'] . '_' . $v['parent_id'] => $v['id'],
			];
		});
		// dd($brands, $subCategories);
		$jsonlist = collect(File::json($jsonPath))->mapWithKeys(function ($v, $k) use ($subCategories, $brands) {
			$sub_k = $v['sub_category_id'] . '_' . $v['category_id'];
			$sub_category_id = $subCategories->get($sub_k, $v['sub_category_id']);
			$v['sub_category_id'] = $sub_category_id;
			$brand = $brands->first(fn($b, $l) => $b['name'] === $v['brand_id'], [
				'id' => $v['brand_id'],
			]);
			$v['brand_id'] = $brand['id'];
			return [
				$k => $v,
			];
		});
		$json = json_encode($jsonlist, JSON_UNESCAPED_UNICODE);
		$json = str_replace('\/', '/', $json);
		File::replace($jsonPath, $json);
		return $jsonlist;
	}

	public static function getCategoriesArray(array $request): array
	{
		$category = [];
		if ($request['category_id'] != null) {
			$category[] = [
				'id' => $request['category_id'],
				'position' => 1,
			];
		}

		if ($request['sub_category_id'] != null) {
			$category[] = [
				'id' => $request['sub_category_id'],
				'position' => 2,
			];
		}

		if (($request['sub_sub_category_id'] ?? null) != null) {
			$category[] = [
				'id' => $request['sub_sub_category_id'],
				'position' => 3,
			];
		}

		return $category;
	}

	public static function importUnitsFromJson($jsonPath)
	{
		$jsonlist = collect(File::json($jsonPath))->pluck('unit')->unique()->values()->all();
		return $jsonlist;
	}

	public static function importProductsFromJson($jsonPath)
	{
		$new_product_approval = getWebConfig(name: 'new_product_approval');
		$jsonlist = collect(File::json($jsonPath))->mapWithKeys(function ($v, $k) use ($new_product_approval) {
			$v['product_type'] = 'physical';
			$v['discount_type'] = 'flat';
			$v['discount'] = 0;
			$v['sub_sub_category_id'] = null;
			$code = Str::random(6);
			$slug = Str::slug($v['name'], '-') . '-' . Str::random(6);
			$row = [];
			return [
				$k => $row,
			];
		})->toArray();
		foreach ($jsonlist as $index => $row) {
			$c = Product::where('name', $row['name'])->where('category_id', $row['category_id'])->first();
			if ($c == null) {
				dd($row);
				$c = Product::create($row);
			}

			$row['id'] = $c?->id ?? 0;
			$jsonlist[$index] = $row;
		}

		return $jsonlist;
	}

	public static function importCategoriesFromJson($jsonPath)
	{
		$jsonlist = collect(File::json($jsonPath))->mapWithKeys(function ($v, $k) {
			return [
				$k => [
					'name' => $v['sub_category_id'],
					'slug' => Str::slug($v['sub_category_id']),
					'icon' => "def.png",
					'icon_storage_type' => null,
					'parent_id' => $v['category_id'],
					'position' => 1,
					'priority' => 1,
				],
			];
		})->unique()->values()->toArray();
		foreach ($jsonlist as $index => $row) {
			$c = Category::where('name', $row['name'])->where('parent_id', $row['parent_id'])->first();
			if ($c == null) {
				dd($row);
				$c = Category::create($row);
			}

			$row['id'] = $c?->id ?? 0;
			$jsonlist[$index] = $row;
		}

		return $jsonlist;
	}

	public static function importBrandsFromJson($jsonPath)
	{
		$jsonlist = collect(File::json($jsonPath))->mapWithKeys(function ($v, $k) {
			return [
				$k => [
					'name' => $v['brand_id'],
					'image' => "def.png",
					'image_storage_type' => null,
					'image_alt_text' => null,
					'status' => 1,
				],
			];
		})->unique()->values()->toArray();
		foreach ($jsonlist as $index => $row) {
			$c = Brand::where('name', $row['name'])->first();
			if ($c == null) {
				//dd($row);
				$c = Brand::create($row);
			}

			$row['id'] = $c?->id ?? 0;
			$jsonlist[$index] = $row;
		}

		return $jsonlist;
	}
}
