<?php

declare(strict_types=1);

namespace Maher\CoreTools\Core\Dynamic;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;
use Illuminate\Support\Str;
/**
 *
 * @template TKey
 * @template TValue
 */
class DynamicObject implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * تخزين جميع الخصائص
     */
    protected array $attributes = [];

    /**
     * -----------------------------
     * الوصول للخصائص كـ $obj->prop
     * -----------------------------
     */
    public function __get($key)
    {
        $mutator = 'get' . Str::studly($key) . 'Prop';
        if (method_exists($this, $mutator)) {
            return $this->$mutator();
        }
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value)
    {
        $mutator = 'set' . Str::studly($key) . 'Prop';
        if (method_exists($this, $mutator)) {
            $value = $this->$mutator($value);
        }
        $this->attributes[$key] = $value;
    }

    public function __isset($key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function __unset($key)
    {
        unset($this->attributes[$key]);
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
	 * @return TValue Can return all value types.
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
     * الحصول على جميع الخصائص
     * -----------------------------
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }
}
