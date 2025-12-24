<?php

declare(strict_types=1);

namespace Maher\CoreTools\Support;


use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Enumerable;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 */
class MArray
{
	/**
	 * The items contained in the collection.
	 *
	 * @var array<TKey, TValue>
	 */
	protected $items = [];
	protected $options = [];
	/**
	 * Create a new collection.
	 *
	 * @param  array<TKey, TValue>|null  $items
	 * @return void
	 */
	public function __construct($items = [])
	{
		$this->items = $items ?? [];
	}
	private function getOption($key, $callback)
	{
		if (!\array_key_exists($key, $this->options)) {
			$this->setOption($key, $callback);
		}

		return $this->options[$key] ?? null;
	}

	private function setOption($key, $value)
	{
		$this->options[$key] = value($value);

		return $this;
	}
	/**
	 * Get all of the items in the collection.
	 *
	 * @return array<TKey, TValue>
	 */
	public function all()
	{
		return $this->items;
	}
	/**
	 * Get all of the items in the collection.
	 *
	 * @return array<TKey, TValue>
	 */
	public function toArray()
	{
		return $this->items;
	}
	/**
	 * Run a filter over each of the items.
	 *@
	 * @param  (callable(TValue, TKey): bool)|null  $callback
	 * @return static
	 */
	public  function filter(?callable $callback = null)
	{
		if (!$this->isEmpty()) {
			$array = $this->items;
			$isAssoc = $this->isAssoc();
			$arr = $callback ? Arr::where($array, $callback) : array_filter($array);


			return new static($this->getValidArray($arr, $isAssoc));
		}
		return new static([]);
	}
	protected  function getValidArray(array $array, $isAssoc = null)
	{
		if (!array_empty($array)) {

			$isAssoc = $isAssoc ?: $this->isAssoc();

			if (!$isAssoc) {
				$array = array_values($array);
			}
			return $array;
		}
		return [];
	}
	/**
	 * Run a filter over each of the items.
	 *
	 * @param  (callable(TValue, TKey): bool)|null  $callback
	 * @return int
	 */
	public  function count(?callable $callback = null): int
	{
		if ($callback == null) {
			return count($this->items);
		}
		$filterd = $this->filter($callback)->all();
		return count($filterd);
	}

	/**
	 * Get the first item from the collection passing the given truth test.
	 *
	 * @template TFirstDefault
	 *
	 * @param  (callable(TValue, TKey): bool)|null  $callback
	 * @param  TFirstDefault|(\Closure(): TFirstDefault)  $default
	 * @return TValue|TFirstDefault
	 */
	public function first(?callable $callback = null, $default = null)
	{
		return Arr::first($this->items, $callback, $default);
	}
	/**
	 * Get an item from the collection by key.
	 *
	 * @template TGetDefault
	 *
	 * @param  TKey  $key
	 * @param  TGetDefault|(\Closure(): TGetDefault)  $default
	 * @return TValue|TGetDefault
	 */
	public function get($key, $default = null)
	{
		if (array_key_exists($key, $this->items)) {
			return $this->items[$key];
		}

		return value($default);
	}
	/**
	 * Determine if an item exists in the collection by key.
	 *
	 * @param  TKey|array<array-key, TKey>  $key
	 * @return bool
	 */
	public function has($key)
	{
		$keys = is_array($key) ? $key : func_get_args();

		foreach ($keys as $value) {
			if (! array_key_exists($value, $this->items)) {
				return false;
			}
		}

		return true;
	}
	/**
	 * Determine if any of the keys exist in the collection.
	 *
	 * @param  mixed  $key
	 * @return bool
	 */
	public function hasAny($key)
	{
		if ($this->isEmpty()) {
			return false;
		}

		$keys = is_array($key) ? $key : func_get_args();

		foreach ($keys as $value) {
			if ($this->has($value)) {
				return true;
			}
		}

		return false;
	}
	/**
	 * Determine if the collection is empty or not.
	 *
	 * @return bool
	 */
	public function isEmpty()
	{
		return empty($this->items);
	}
	/**
	 * Get the keys of the collection items.
	 *
	 * @return static<int, TKey>
	 */
	public function keys()
	{
		return new static(array_keys($this->items));
	}
	/**
	 * Get the last item from the collection.
	 *
	 * @template TLastDefault
	 *
	 * @param  (callable(TValue, TKey): bool)|null  $callback
	 * @param  TLastDefault|(\Closure(): TLastDefault)  $default
	 * @return TValue|TLastDefault
	 */
	public function last(?callable $callback = null, $default = null)
	{
		return Arr::last($this->items, $callback, $default);
	}
	/**
	 * Run an associative map over each of the items.
	 *
	 * The callback should return an associative array with a single key/value pair.
	 *
	 * @template TMapWithKeysKey of array-key
	 * @template TMapWithKeysValue
	 *
	 * @param  callable(TValue, TKey): array<TMapWithKeysKey, TMapWithKeysValue>  $callback
	 * @return static<TMapWithKeysKey, TMapWithKeysValue>
	 */
	public function mapWithKeys(callable $callback)
	{
		return new static(Arr::mapWithKeys($this->items, $callback));
	}
	/**
	 * Select specific values from the items within the collection.
	 *
	 * @param  \Illuminate\Support\Enumerable<array-key, TKey>|array<array-key, TKey>|string|null  $keys
	 * @return static
	 */
	public function select($keys)
	{
		if (is_null($keys)) {
			return new static($this->items);
		}

		if ($keys instanceof Enumerable) {
			$keys = $keys->all();
		}

		$keys = is_array($keys) ? $keys : func_get_args();

		return new static(Arr::select($this->items, $keys));
	}
	/**
	 * Get and remove an item from the collection.
	 *
	 * @template TPullDefault
	 *
	 * @param  TKey  $key
	 * @param  TPullDefault|(\Closure(): TPullDefault)  $default
	 * @return TValue|TPullDefault
	 */
	public function pull($key, $default = null)
	{
		return Arr::pull($this->items, $key, $default);
	}
	/**
	 * Sort through each item with a callback.
	 *
	 * @param  (callable(TValue, TValue): int)|null|int  $callback
	 * @return static
	 */
	public function sort($callback = null)
	{
		$items = $this->items;

		$callback && is_callable($callback)
			? uasort($items, $callback)
			: asort($items, $callback ?? SORT_REGULAR);

		return new static($items);
	}
	/**
	 * Sort the collection keys.
	 * 
	 * @param  int  $options
	 * @param  bool  $descending
	 * @return static
	 */
	public  function sortKeys($options = SORT_REGULAR, $descending = false)
	{
		$items = $this->items;
		$descending ? krsort($items, $options) : ksort($items, $options);
		return new static($items);
	}
	/**
	 * Sort the collection keys.
	 *
	 * @param  int  $options
	 * @param  bool  $descending
	 * @return static
	 */
	public  function sortKeysDesc($options = SORT_REGULAR)
	{

		return $this->sortKeys($options, true);
	}

	/**
	 * Sort the collection using the given callback.
	 *
	 * @param  array<array-key, (callable(TValue, TValue): mixed)|(callable(TValue, TKey): mixed)|string|array{string, string}>|(callable(TValue, TKey): mixed)|string  $callback
	 * @param  int  $options
	 * @param  bool  $descending
	 * @return static
	 */
	public function sortBy($callback, $options = SORT_REGULAR, $descending = false)
	{
		if (is_array($callback) && ! is_callable($callback)) {
			return $this->sortByMany($callback, $options);
		}

		$results = [];

		$callback = $this->valueRetriever($callback);

		// First we will loop through the items and get the comparator from a callback
		// function which we were given. Then, we will sort the returned values and
		// grab all the corresponding values for the sorted keys from this array.
		foreach ($this->items as $key => $value) {
			$results[$key] = $callback($value, $key);
		}

		$descending ? arsort($results, $options)
			: asort($results, $options);

		// Once we have sorted all of the keys in the array, we will loop through them
		// and grab the corresponding model so we can set the underlying items list
		// to the sorted version. Then we'll just return the collection instance.
		foreach (array_keys($results) as $key) {
			$results[$key] = $this->items[$key];
		}

		return new static($results);
	}

	/**
	 * Sort the collection using multiple comparisons.
	 *
	 * @param  array<array-key, (callable(TValue, TValue): mixed)|(callable(TValue, TKey): mixed)|string|array{string, string}>  $comparisons
	 * @param  int  $options
	 * @return static
	 */
	protected function sortByMany(array $comparisons = [], int $options = SORT_REGULAR)
	{
		$items = $this->items;

		uasort($items, function ($a, $b) use ($comparisons, $options) {
			foreach ($comparisons as $comparison) {
				$comparison = Arr::wrap($comparison);

				$prop = $comparison[0];

				$ascending = Arr::get($comparison, 1, true) === true ||
					Arr::get($comparison, 1, true) === 'asc';

				if (! is_string($prop) && is_callable($prop)) {
					$result = $prop($a, $b);
				} else {
					$values = [data_get($a, $prop), data_get($b, $prop)];

					if (! $ascending) {
						$values = array_reverse($values);
					}

					if (($options & SORT_FLAG_CASE) === SORT_FLAG_CASE) {
						if (($options & SORT_NATURAL) === SORT_NATURAL) {
							$result = strnatcasecmp($values[0], $values[1]);
						} else {
							$result = strcasecmp($values[0], $values[1]);
						}
					} else {
						$result = match ($options) {
							SORT_NUMERIC => intval($values[0]) <=> intval($values[1]),
							SORT_STRING => strcmp($values[0], $values[1]),
							SORT_NATURAL => strnatcmp($values[0], $values[1]),
							SORT_LOCALE_STRING => strcoll($values[0], $values[1]),
							default => $values[0] <=> $values[1],
						};
					}
				}

				if ($result === 0) {
					continue;
				}

				return $result;
			}
		});

		return new static($items);
	}

	/**
	 * Sort the collection in descending order using the given callback.
	 *
	 * @param  array<array-key, (callable(TValue, TValue): mixed)|(callable(TValue, TKey): mixed)|string|array{string, string}>|(callable(TValue, TKey): mixed)|string  $callback
	 * @param  int  $options
	 * @return static
	 */
	public function sortByDesc($callback, $options = SORT_REGULAR)
	{
		if (is_array($callback) && ! is_callable($callback)) {
			foreach ($callback as $index => $key) {
				$comparison = Arr::wrap($key);

				$comparison[1] = 'desc';

				$callback[$index] = $comparison;
			}
		}

		return $this->sortBy($callback, $options, true);
	}



	/**
	 * Sort the collection keys using a callback.
	 *
	 * @param  callable(TKey, TKey): int  $callback
	 * @return static
	 */
	public function sortKeysUsing(callable $callback)
	{
		$items = $this->items;

		uksort($items, $callback);

		return new static($items);
	}
	/**
	 * Reset the keys on the underlying array.
	 *
	 * @return static<int, TValue>
	 */
	public function values()
	{
		return new static(array_values($this->items));
	}
	/**
	 * Determine if the given value is callable, but not a string.
	 *
	 * @param  mixed  $value
	 * @return bool
	 */
	protected function useAsCallable($value)
	{
		return ! is_string($value) && is_callable($value);
	}

	/**
	 * Get a value retrieving callback.
	 *
	 * @param  callable|string|null  $value
	 * @return callable
	 */
	protected function valueRetriever($value)
	{
		if ($this->useAsCallable($value)) {
			return $value;
		}

		return fn($item) => data_get($item, $value);
	}

	/**
	 * Make a function to check an item's equality.
	 *
	 * @param  mixed  $value
	 * @return \Closure(mixed): bool
	 */
	protected function equality($value)
	{
		return fn($item) => $item === $value;
	}

	/**
	 * Make a function using another function, by negating its result.
	 *
	 * @param  \Closure  $callback
	 * @return \Closure
	 */
	protected function negate(Closure $callback)
	{
		return fn(...$params) => ! $callback(...$params);
	}

	/**
	 * Make a function that returns what's passed to it.
	 *
	 * @return \Closure(TValue): TValue
	 */
	protected function identity()
	{
		return fn($value) => $value;
	}
	/**
	 * Return only unique items from the collection array.
	 *
	 * @param  (callable(TValue, TKey): mixed)|string|null  $key
	 * @param  bool  $strict
	 * @return static
	 */
	public function unique($key = null, $strict = false)
	{
		if (is_null($key) && $strict === false) {
			return new static($this->getValidArray(array_unique($this->items, SORT_REGULAR)));
		}

		$callback = $this->valueRetriever($key);

		$exists = [];

		return $this->reject(function ($item, $key) use ($callback, $strict, &$exists) {
			if (in_array($id = $callback($item, $key), $exists, $strict)) {
				return true;
			}

			$exists[] = $id;
		});
	}


	public function findDuplicates(?array $specificKeys = null, bool $caseInsensitive = false): array
	{
		$collection = collect($this->items);

		if ($specificKeys === null) {
			$specificKeys = $collection->reduce(function ($carry, $item) {
				return $carry === null ? array_keys($item) : array_intersect($carry, array_keys($item));
			});
		}

		return $collection
			->groupBy(function ($item) use ($specificKeys, $caseInsensitive) {
				return implode('|', array_map(function ($key) use ($item, $caseInsensitive) {
					$value = data_get($item, $key);
					$value = is_array($value) ? json_encode($value) : (string)$value;
					return $caseInsensitive ? strtolower($value) : $value;
				}, $specificKeys));
			})
			->filter(fn($group) => $group->count() > 1)
			->map(fn($group, $key) => [
				'item' => $group->first(),
				'count' => $group->count(),
			])
			->values()
			->toArray();
	}

	public  function extractDuplicatesFullCompare(bool $withCount = false)
	{
		return collect($this->items)
			->groupBy(function ($item) {
				// تحويل المصفوفة إلى سلسلة للمقارنة
				return json_encode($item, JSON_UNESCAPED_UNICODE);
			})
			->filter(function ($group) {
				return $group->count() > 1;
			})
			->map(function ($group, $index) use ($withCount) {
				return $withCount ? [
					'item' => $group->first(),
					'count' => $group->count()
				] : $group->first();
			})
			->values()
			->all();
	}
	/**
	 *get all elements that doublicate rows
	
	 * @return array
	 */
	public  function doublicateRows()
	{

		$doublicate = $this->extractDuplicatesFullCompare();

		/* if (is_null($key)) {
			$keys = $this->unique()->keys()->All();
			if (count($keys) === $this->count()) {
				return $doublicate;
			}
			foreach ($keys as $key) {
				$f = $this->filter(fn($v, $k) => $k === $key);
				if ($f->count() > 1) {
					$doublicate[$key] = $f->values()->all();
				}
			}
		}

 */
		return $doublicate;
	}
	/**
	 * Create a collection of all elements that do not pass a given truth test.
	 *
	 * @param  (callable(TValue, TKey): bool)|bool|TValue  $callback
	 * @return static
	 */
	public function reject($callback = true)
	{
		$useAsCallable = $this->useAsCallable($callback);

		return $this->filter(function ($value, $key) use ($callback, $useAsCallable) {
			return $useAsCallable
				? ! $callback($value, $key)
				: $value != $callback;
		});
	}
	public  function isAssoc(): bool
	{
		return	$this->getOption('isAssoc', function () {
			return  $this->isEmpty() ? false : Arr::isAssoc($this->items);
		});
	}
	public  function getColumns(): array
	{
		return	$this->getOption('Columns', function () {
			if ($this->isEmpty()) {
				return [];
			}
			$row = $this->isAssoc() ? $this->items : $this->items[0];
			return array_keys($row);
		});
	}


	public  function toJsonArray(int $depth = 512)
	{
		$array = $this->items;
		//JSON_FORCE_OBJECT
		//$json = json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		$json = json_encode($array, JSON_UNESCAPED_UNICODE, $depth);
		return stripslashes($json);
	}

	public  function toJson(int $depth = 512)
	{

		if ($this->isEmpty()) {
			return '{}';
		}

		$array = $this->items;
		/* if (is_array($array) || is_object($data)) {
         		} */
		return stripslashes(json_encode($array, JSON_FORCE_OBJECT, $depth));
	}
}