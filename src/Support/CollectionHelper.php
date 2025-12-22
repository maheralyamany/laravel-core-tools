<?php

declare(strict_types=1);

namespace Maher\CoreTools\Support;

use Illuminate\Support\Collection;
use Illuminate\Pagination\{LengthAwarePaginator, Paginator};

final class CollectionHelper
{
	public static function collectionPaginate($collection, $perPage, $total = null, $page = null, $pageName = 'page')
	{
		$page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
		return new LengthAwarePaginator($collection->forPage($page, $perPage), $total ?: $collection->count(), $perPage, $page, [
			'path' => LengthAwarePaginator::resolveCurrentPath(),
			'pageName' => $pageName,
		]);
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
	 * @param Collection $collection
	 * @param (callable(TValue, TKey):bool)|null $filter
	 * @param  callable(TValue, TKey): array<TMapWithKeysKey, TMapWithKeysValue>|null  $select
	 * @return Collection<TMapWithKeysKey, TMapWithKeysValue>
	 */
	public static function filterMapWithKeys($collection, ?callable $filter, callable $select)
	{
		$items = [];
		foreach ($collection as $key => $value) {
			// --- Filter callback ---
			if ($filter && !$filter($value, $key)) continue;
			// --- Select / map callback ---
			if ($select) {
				$mapped = $select($value, $key);
				if (!m_empty($mapped)) {
					foreach ($mapped as $k => $val) {
						$items[$k] = $val;
					}
				}
			} else {
				$items[$key] = $value;
			}
		}
		return new Collection($items);
	}
}
