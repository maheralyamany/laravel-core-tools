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
class MxCollection extends Collection
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
     * @return static<array-key, static<array-key, TValue>>
     */
    public function mxGroupBy($groupBy, $forgetKey = true, $preserveKeys = false)
    {
        if (!$this->useAsCallable($groupBy) && is_array($groupBy)) {
            $nextGroups = $groupBy;
            $groupBy = array_shift($nextGroups);
            //dd($groupBy);
        }
        
        $currGroupKey = $groupBy;
        $groupBy = $this->valueRetriever($groupBy);
        $results = [];
        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);
            if (is_array($value) && $forgetKey && array_key_exists($currGroupKey, $value)) {
                unset($value[$currGroupKey]);
            }
            
            /* if (empty($nextGroups))
            			dd($results, $currGroupKey, $groupKeys, $value); */
            if (!is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }
            
            foreach ($groupKeys as $groupKey) {
                $groupKey = match (true) {
                    is_bool($groupKey) => (int) $groupKey,
                    $groupKey instanceof BackedEnum => $groupKey->value,
                    $groupKey instanceof Stringable => (string) $groupKey,
                    default => $groupKey,
                };
                if (!array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static();
                }
                
                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }
        
        $result = new static($results);
        if ($nextGroups !== []) {
            $result = $result->map->mxGroupBy($nextGroups, $forgetKey, $preserveKeys);
        }
        
        return $result;
    }

}