<?php

declare (strict_types=1);
namespace Maher\CoreTools\Support;

use BackedEnum;
use Stringable;
use Illuminate\Support\Collection;
/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 */
class MCollection extends Collection
{
    public function __construct($items = [])
    {
        parent::__construct($items);
    }

    /**
	 * Group an associative array by a field or using a callback.
	 *
	 * @param  (callable(TValue, TKey): array-key)|array|string  $groupBy
	 * @param  bool  $preserveKeys
	 * @param  bool  $forgetKey
	 * @param  bool  $pluckEmpty
	 * @return static<array-key, static<array-key, TValue>>
	 */
	public function mGroupBy($groupBy, $forgetKey = true, $preserveKeys = false, bool $pluckEmpty = false)
	{
		
		if (! $this->useAsCallable($groupBy) && \is_array($groupBy)) {
			$nextGroups = $groupBy;

			$groupBy = array_shift($nextGroups);
			//dd($groupBy);
		}
		$currGroupKey = $groupBy;
		$groupBy = $this->valueRetriever($groupBy);

		$results = [];

		foreach ($this->items as $key => $value) {
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
					$results[$groupKey] = new static;
				}
				if ($pluckEmpty&&empty($nextGroups) && is_array($value) && empty($value)) {
					$results[$groupKey] = $groupKey;
					//dd($value, $groupKey, $results[$groupKey]);
				} else {
					$results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
				}
			}
		}

		$result = new static($results);

		if (! empty($nextGroups)) {
			$result = $result->map->mGroupBy($nextGroups, $forgetKey, $preserveKeys,$pluckEmpty);
		}

		return $result;
	}

}