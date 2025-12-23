<?php

declare(strict_types=1);

namespace Maher\CoreTools\Core\Dynamic;

use Illuminate\Support\Str;
use ReflectionClass;

use ArrayIterator;


/* abstract class */

trait DyHasAttributes /* implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable */
{

	protected array $attributes = [];


	/**
	 * -----------------------------
	 * الوصول للخصائص كـ $obj->prop
	 * -----------------------------
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}

	public function __set($key, $value)
	{
		$this->setAttribute($key, $value);
	}

	public function __isset($key): bool
	{
		return $this->hasProperty($key) || $this->hasAttribute($key);
	}

	public function __unset($key)
	{
		$this->attributes = unset_exists($this->attributes, $key);
		//unset($this->attributes[$key]);
	}

	/**
	 * -----------------------------
	 * الوصول كمصفوفة $obj['prop']
	 * -----------------------------
	 */
	public function offsetExists($offset): bool
	{

		return isset($this->$offset);
	}
	/**
	 * Offset to retrieve
	 * Returns the value at specified offset.
	 *
	 * @param mixed $offset The offset to retrieve.
	 * @return mixed Can return all value types.
	 */
	public	function offsetGet(mixed $offset): mixed
	{
		return $this->$offset;
	}

	public function offsetSet($offset, $value): void
	{
		$this->$offset = $value;
	}

	public function offsetUnset($offset): void
	{
		unset($this->$offset);
	}

	/**
	 * -----------------------------
	 * Countable
	 * -----------------------------
	 */
	public function count(): int
	{
		return count($this->attributes);
	}

	/**
	 * -----------------------------
	 * IteratorAggregate لدعم foreach
	 * -----------------------------
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->attributes);
	}

	/**
	 * -----------------------------
	 * JsonSerializable لدعم json_encode
	 * -----------------------------
	 */
	public function jsonSerialize(): array
	{
		return $this->attributes;
	}

	/**
	 * -----------------------------
	 * الوصول للخصائص الديناميكية
	 * -----------------------------
	 */
	public function getAttribute($key, $default = null)
	{
		if (!$key) return value($default);

		return $this->getAttributeValue($key, function () use ($key, $default) {
			$mutatorMethod = $this->getValidMutatorMethodName($key);


			if (!is_null($mutatorMethod)) {
				$value = $this->{$mutatorMethod}();
				$this->setAttribute($key, $value);
				//return $this->mutateAttribute($key);
				return $value;
			}
			return value($default);
		});
	}

	public function getAttributeValue($key, $default = null)
	{
		if (!$key) return value($default);

		if ($this->hasProperty($key)) {
			return $this->getPropValue($key, $default);
		} elseif ($this->hasAttribute($key)) {
			return $this->attributes[$key] ?? value($default);
		}

		return value($default);
	}

	public function setAttribute($key, $value): self
	{

		if ($this->hasProperty($key)) {
			$this->setPropValue($key, $value);
		} elseif (!is_null($mutatorMethod = $this->getValidMutatorMethodName($key, 'set'))) {
			$this->$mutatorMethod($value);
		} else {
			$this->attributes[$key] = $value;
		}
		// استدعاء set mutator إذا موجود
		/* $mutatorMethod = 'set' . Str::studly($key) . 'Prop';
		if (method_exists($this, $mutatorMethod)) {
			$value = $this->$mutatorMethod($value);
		}
		if ($this->hasProperty($key)) {
			$this->setPropValue($key, $value);
		} else {
			$this->attributes[$key] = $value;
		} */

		return $this;
	}

	public function hasProperty($key): bool
	{
		return property_exists($this, $key);
	}

	public function hasAttribute($key): bool
	{
		return array_key_exists($key, $this->attributes);
	}

	protected function getPropValue(string $propertyName, $default = null)
	{
		$value = null;
		$reflection = new ReflectionClass($this);

		$property = $reflection->getProperty($propertyName);
		if ($property->isStatic()) {
			$value = $reflection->getStaticPropertyValue($propertyName);
		} elseif ($property->isInitialized($this)) {
			$value = $property->getValue($this);
		}

		return $value ?? value($default);
	}

	protected function setPropValue(string $propertyName, $value)
	{
		$reflection = new ReflectionClass($this);
		$property = $reflection->getProperty($propertyName);

		if ($property->isStatic()) {
			$reflection->setStaticPropertyValue($propertyName, $value);
		} else {
			$property->setValue($this, $value);
		}

		return $this;
	}
	/**
	 * Determine if a get mutator exists for an attribute.
	 *
	 * @param  string  $key
	 * @param  string|null  $prefix
	 * @param  string|null  $suffix
	 * @return bool
	 */
	protected function hasGetMutator($key, $prefix = 'get', $suffix = "Prop"): bool
	{

		$method = $this->getMutatorName($key, $prefix, $suffix);
		return method_exists($this, $method);
	}
	/**
	 * get mutator method name.
	 *
	 * @param  string  $key
	 * @param  string|null  $prefix
	 * @param  string|null  $suffix
	 * @return string
	 */
	protected function getMutatorName($key, $prefix = 'get', $suffix = "Prop"): string
	{
		$prefix = trim($prefix ?? '');
		$suffix = trim($suffix ?? '');
		return $prefix . Str::studly($key) . $suffix;
	}
	/**
	 * get mutator method name.
	 *
	 * @param  string  $key
	 * @param  string|null  $prefix
	 
	 * @return string|null
	 */
	protected function getValidMutatorMethodName($key, $prefix = 'get')
	{
		foreach (['0' => 'Prop', '1' => ''] as $k => $suffix) {
			$method = $this->getMutatorName($key, $prefix, $suffix);
			if (method_exists($this, $method)) {
				return $method;
			}
		}

		return null;
	}
	/**
	 * Get the value of an attribute using its mutator.
	 *
	 * @param  string  $key
	 * @param  string|null  $prefix
	 * @param  string|null  $suffix
	 * @return mixed
	 */
	protected function mutateAttribute($key, $prefix = 'get', $suffix = "Prop")
	{
		$method = $this->getMutatorName($key, $prefix, $suffix);
		$value = $this->{$method}();
		$this->setAttribute($key, $value);
		return $value;
	}

	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function setAttributes(array $attributes): self
	{
		$this->attributes = $attributes;
		return $this;
	}
}
