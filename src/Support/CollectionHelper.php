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
	 * Group an associative array by a field or using a callback.
	 * @template TKey of array-key
	 * @template-covariant TValue
	 * @param  Collection  $collection
	 * @param  (callable(TValue, TKey): array-key)|array|string  $groupBy
	 * @param  bool  $preserveKeys
	 * @param  bool  $forgetKey
	 * @param  bool  $pluckEmpty
	 * @return Collection<array-key, Collection<array-key, TValue>>
	 */
	public static function mGroupBy($collection, $groupBy, $forgetKey = true, $preserveKeys = false, bool $pluckEmpty = false)
	{
		if (! $collection->useAsCallable($groupBy) && \is_array($groupBy)) {
			$nextGroups = $groupBy;

			$groupBy = array_shift($nextGroups);
			//dd($groupBy);
		}
		$currGroupKey = $groupBy;
		$groupBy = $collection->valueRetriever($groupBy);

		$results = [];

		foreach ($collection->items as $key => $value) {
			$groupKeys = $groupBy($value, $key);
			if (\is_array($value) && $forgetKey && array_key_exists($currGroupKey, $value)) {
				unset($value[$currGroupKey]);
			}
			/* if (empty($nextGroups))
				dd($results, $currGroupKey, $groupKeys, $value); */
			if (! \is_array($groupKeys)) {
				$groupKeys = [$groupKeys];
			}

			foreach ($groupKeys as $groupKey) {

				$groupKey = match (true) {
					is_bool($groupKey) => (int) $groupKey,
					$groupKey instanceof \BackedEnum => $groupKey->value,
					$groupKey instanceof \Stringable => (string) $groupKey,
					default => $groupKey,
				};

				if (! array_key_exists($groupKey, $results)) {
					$results[$groupKey] = new Collection;
				}
				if ($pluckEmpty && empty($nextGroups) && is_array($value) && empty($value)) {
					$results[$groupKey] = $groupKey;
					//dd($value, $groupKey, $results[$groupKey]);
				} else {
					$results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
				}
			}
		}

		$result = new Collection($results);

		if (! empty($nextGroups)) {
			$result = $result->map->mGroupBy($nextGroups, $forgetKey, $preserveKeys, $pluckEmpty);
		}

		return $result;
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
