<?php

declare(strict_types=1);

namespace Maher\CoreTools\Core\Dynamic;

trait DyDataStoreTrait
{
	protected array $dyData = [];
	public function getAllData()
	{
		return $this->dyData;
	}
	/**
	 * Summary of getDyData
	 * @param string $key
	 * @param callable():mixed $callback
	 * @param bool $forced
	 * @param (callable(mixed): bool)|null $checkValue
	 */
	public function getDyData($key, callable $callback, bool $forced = false, ?callable $checkValue = null)
	{
		if ($forced || !\array_key_exists($key, $this->dyData)) {
			$this->dyData[$key] = $callback();
		}
		$value = $this->dyData[$key] ?? null;
		if (!$forced && $checkValue != null && !$checkValue($value)) {
			$value = $callback();
			$this->dyData[$key] = $value;
		}
		return $value;
	}
	public function getDySubData(string $key, $subKey, callable $callback, bool $forced = false)
	{
		$result = $this->getDyData($key, fn() => [], $forced);
		if (!\array_key_exists($subKey, $result)) {
			$result[$subKey] = value($callback);
			$this->setDyData($key, $result);
		}
		return $result[$subKey];
	}
	public function setDyData($key, $value)
	{
		$this->dyData[$key] = value($value);
	}
	public function clearDyData()
	{
		$this->dyData = [];
	}
	public function clearAll(array $params = [])
	{
		$this->dyData = [];
	}
}
