<?php

declare(strict_types=1);

namespace Maher\CoreTools\Core\Descriptor;

enum DescriptorType: string
{
	case _CLASS = 'class';
	case _TRAIT = 'trait';
	case _INTERFACE = 'interface';
	case _OTHER = '';


	public function getLabel(): string|null
	{
		return match ($this) {
			self::_CLASS => 'class',
			self::_TRAIT => 'trait',
			self::_INTERFACE => 'interface',
			default => null,
		};
	}
	/**
	 * @param  int|string|static $name
	 * @return static
	 */
	public static function fromName($name): self
	{

		if ($name instanceof static) {
			return $name;
		}

		return match (strval($name)) {
			'1', 'class', 'CLASS', 1 => self::_CLASS,
			'2', 'trait', 'TRAIT', 2 => self::_TRAIT,
			'3', 'interface', 'INTERFACE', 3 => self::_INTERFACE,
			default => self::_OTHER,
		};
	}
	public function toString(): string|null
	{
		return $this->value;
	}
	/**
	 * @param  mixed $value
	 * @return static
	 */
	public static function fromValue($value): self
	{
		return self::from($value);
	}

	/**
	 * Summary of isMatch
	 * @param int|string|static $action
	 */
	public function isMatch($action): bool
	{
		return self::fromName($action) === $this;
	}

	/**
	 * @param array $actions
	 */
	public function isMatchIn(...$actions): bool
	{
		return collect($actions)->filter(fn($action, $k): bool => self::fromName($action) == $this)->count() > 0;
	}


	public function isValid(): bool
	{
		return $this !== self::_OTHER;
	}
	public function isClass(): bool
	{
		return $this === self::_CLASS;
	}
	public function isTrait(): bool
	{
		return $this === self::_TRAIT;
	}
	public function isInterface(): bool
	{
		return $this === self::_INTERFACE;
	}
}
