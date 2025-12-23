<?php

declare(strict_types=1);

namespace Maher\CoreTools\Core\Descriptor;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Maher\CoreTools\Core\Dynamic\DyHasAttributes;
use Maher\CoreTools\Support\ArrayHelper;
use ReflectionClass;
use ReflectionMethod;

class ReflectionDescriptor  implements \ArrayAccess
{
	use DyHasAttributes;
	public array $extraAttrs = [];


	
	public function __construct(array $attributes)
	{
		try {
			$shortName = $attributes['shortName'];

			$nameSpace = $attributes['nameSpace'] ?? '';
			$fullName = $attributes['fullName'] ?? null;
			$path = $attributes['path'] ?? null;
			$reflectionType = $attributes['type'] ?? ($attributes['reflectionType'] ?? '');
			$fullName = m_empty($fullName) ? $this->getValidFullName($nameSpace, $shortName) : $fullName;
			$reflectionClass = new ReflectionClass($fullName);
			//$reflectionClass->getNamespaceName()
			$path = m_empty($path) ? $reflectionClass->getFileName() : $path;
			$attributes['nameSpace'] = $nameSpace;
			$attributes['reflectionClass'] = $reflectionClass;
			$attributes['shortName'] = $shortName;
			$attributes['path'] = $path;
			$attributes['fullName'] = $fullName;
			$attributes['reflectionType'] = $this->getValidReflectionType($reflectionType, $reflectionClass);
			$this->attributes = $attributes;
		} catch (\Exception $th) {
		
			//throw $th;
		}
	}


	public static function new(string $nameSpace, string $shortName, DescriptorType|string $type = DescriptorType::_CLASS, string $path = '', string|null $fullName = null): static
	{
		$attributes = [
			'type' => $type,
			'shortName' => $shortName,
			'nameSpace' => $nameSpace,
			'fullName' => $fullName,
		];
		return new ReflectionDescriptor($attributes);
	}
	public static function getShortClassName(string $fqcn): string
	{
		if (!str_contains($fqcn, '\\'))
			return $fqcn;
		$pieces = explode('\\', $fqcn);
		return end($pieces);
	}


	/**
	 * Get the value of fullName
	 */
	public function getFullNameProp()
	{

		return $this->getValidFullName($this->nameSpace, $this->shortName);
	}
	public function getValidFullName($nameSpace, $shortName)
	{
		if (m_empty($nameSpace))
			return $shortName;
		return $nameSpace . "\\" . $shortName;
	}


	/**
	 * Get the value of className
	 */
	public function getClassName()
	{
		return $this->fullName;
	}

	/**
	 * Set the value of className
	 *
	 * @return  self
	 */
	public function setClassName($className)
	{
		return	$this->fullName = $className;
	}


	/**
	 * Get the value of reflectionType
	 */
	public function getReflectionTypeValue(): DescriptorType
	{

		return $this->getAttributeValue('reflectionType', DescriptorType::_CLASS);
	}

	/**
	 * Set the value of reflectionType
	 *
	 * @return  self
	 */
	public function setReflectionType(DescriptorType|string $reflectionType)
	{
		$this->attributes['reflectionType'] = $this->getValidReflectionType($reflectionType);
		return $this;
	}
	public function getValidReflectionType(DescriptorType|string $reflectionType, ReflectionClass|null $ref = null)
	{
		$reflectionType = DescriptorType::fromName($reflectionType);
		if ($reflectionType->isMatch(DescriptorType::_OTHER)) {
			$ref = $ref ?? $this->getReflection();
			if ($ref->isTrait())
				return DescriptorType::_TRAIT;
			if ($ref->isInterface())
				return DescriptorType::_INTERFACE;
			return DescriptorType::_CLASS;
		}
		return $reflectionType;
	}



	public function isClass()
	{
		return $this->getReflectionTypeValue()->isClass();
	}
	public function isTrait()
	{
		return $this->getReflectionTypeValue()->isTrait();
	}
	public function isInterface()
	{
		return $this->getReflectionTypeValue()->isInterface();
	}
	/**
	 * Summary of isMatch
	 * @param int|string|DescriptorType $reflectionType 
	 */
	public function isMatchReflectionType($reflectionType): bool
	{

		return $this->getReflectionTypeValue()->isMatch($reflectionType);
	}
	public function isSubclassOf(string $class, bool $allow_string = true): bool
	{
		$className = $this->className;
		return is_subclass_of($className,  $class, $allow_string);
	}
	public function newInstanceOffClass()
	{
		try {
			$className = $this->className;
			return new $className();
		} catch (\Throwable $th) {
			//throw $th;
		}
		return null;
	}
	/**
	 * Get summary of reflectionClass
	 *
	 * @return  ReflectionClass
	 */
	public function getReflectionClass()
	{
		$className = $this->className;
		$reflectionClass = new ReflectionClass($className);
		return $reflectionClass;
	}
	/**
	 * Get summary of reflectionClass
	 *
	 * @return  ReflectionClass
	 */
	public function getReflection()
	{
		return $this->reflectionClass;
	}
	/**
	 * get reflection Methods
	 * @return MethodDescriptor[]
	 */
	public function getReflectionMethods()
	{
		//
		$ref = $this->getReflection();
		$className = $this->className;
		$isTrait = $this->isTrait();
		$isClass = $this->isClass();
		$allMethods = $ref->getMethods();

		$methods = ArrayHelper::filterMapWithKeys($allMethods, function (ReflectionMethod $method, $k) use ($className, $isTrait, $isClass) {
			/* $decl = $method->getDeclaringClass();
			if ($method->getName() === '__construct' || $decl->getName() !== $className||(!(($decl->getFileName() === $method->getFileName() && $decl->getStartLine() <= $method->getStartLine() && $decl->getEndLine() >= $method->getEndLine()))&&$isClass))
				return null; */
			$m = new MethodDescriptor($method, $this);
			if (!$m->isInClass($className, $isTrait))
				return null;
			$m->getMethodBody();
			//dd($m, $this);
			return [$k => $m];
		}, true);
		/* $methods = collect($allMethods)->filter(function (ReflectionMethod $method) use ($className, $isClass) {

			$decl = $method->getDeclaringClass();
			if ($method->getName() === '__construct' || $decl->getName() !== $className)
				return false;
			$isValid = ($decl->getFileName() === $method->getFileName() && $decl->getStartLine() <= $method->getStartLine() && $decl->getEndLine() >= $method->getEndLine());
			if (!$isValid && $isClass)
				return false;
			return true;
		})->values()->all(); */

		return $methods;
	}
	public function getFilePathProp()
	{
		return $this->path ??  $this->getReflection()->getFileName();
	}

	public function getFileLinesProp(): array
	{


		$lines = file($this->filePath);


		return $lines;
	}
	public function getFileContentProp(): string
	{
		$lines = $this->getLines();
		//return file_get_contents($this->filePath);
		return \implode('', $lines);
	}
	public function getContent(): string
	{
		return $this->fileContent;
	}
	public function getTokenAll(): array
	{
		$content = $this->getContent();
		return token_get_all($content);
	}
	public function getLines(): array
	{
		return $this->fileLines;
	}
	/**
	 * get reflection Methods
	 * @return MethodDescriptor[]
	 */
	public function getMethods()
	{

		return $this->reflectionMethods;
	}
	public function isDescriptorExists()
	{
		$className = $this->className;
		$exists = match ($this->getReflectionTypeValue()) {
			DescriptorType::_CLASS => class_exists($className),
			DescriptorType::_TRAIT => trait_exists($className),
			DescriptorType::_INTERFACE => interface_exists($className),
			default => false,
		};
		return $exists;
	}
	public function getContents()
	{
		return  File::get($this->path);
	}

	/**
	 * Get the value of extraAttrs
	 */
	public function getExtraAttrs()
	{
		if (\is_null($this->extraAttrs))
			$this->extraAttrs = [];
		return $this->extraAttrs;
	}

	/**
	 * Set the value of extraAttrs
	 *
	 * @return  self
	 */
	public function setExtraAttrs(array $extraAttrs)
	{
		$this->extraAttrs = $extraAttrs;
		return $this;
	}
	public function addExtraAttr($value, $key = null)
	{
		$this->getExtraAttrs();
		if (\is_null($key))
			$this->extraAttrs[] = $value;
		else
			$this->extraAttrs[$key] = $value;
		return $this;
	}
}
