<?php

declare(strict_types=1);

namespace Maher\CoreTools\Core\Descriptor;

use Maher\CoreTools\Core\Dynamic\DyDataStoreTrait;
use ReflectionClass;
use ReflectionMethod;

class MethodDescriptor
{
	use DyDataStoreTrait;
	/**
	 *  method
	 * @var ReflectionMethod
	 */
	protected  $method;


	/**
	 * class Descriptor
	 * @var ReflectionDescriptor
	 */
	protected  $descriptor;



	public function __construct(ReflectionMethod $method, ReflectionDescriptor $descriptor)
	{

		$this->clearDyData();
		$this->descriptor = $descriptor;
		$this->method = $method;
		$this->getStartLine();
		$this->getEndLine();
	}
	/**
	 * Get class Descriptor
	 *
	 * @return  ReflectionDescriptor
	 */
	public function getDescriptor()
	{
		if (is_null($this->descriptor)) {
			$ref = $this->method->getDeclaringClass();
			$this->descriptor =  ReflectionDescriptor::new($ref->getNamespaceName(), $ref->getShortName(), DescriptorType::_OTHER, $ref->getFileName());
		}
		return $this->descriptor;
	}

	/**
	 * Set class Descriptor
	 *
	 * @param  ReflectionDescriptor  $descriptor  class Descriptor
	 *
	 * @return  self
	 */
	public function setDescriptor(ReflectionDescriptor $descriptor)
	{
		$this->descriptor = $descriptor;

		return $this;
	}
	public function getDocComment()
	{
		return $this->method->getDocComment();
	}

	/**
	 * Get method
	 *
	 * @return  ReflectionMethod
	 */
	public function getMethod()
	{
		return $this->method;
	}


	/**
	 * Get declaring Class
	 *
	 * @return  ReflectionClass
	 */
	public function getDeclaringClass(): ReflectionClass
	{
		return $this->getDyData('DeclaringClass', function () {
			return  $this->method->getDeclaringClass();
		});
	}

	/**
	 * Get the value of startLine
	 */
	public function getStartLine(): int
	{
		return $this->getDyData('StartLine', function () {
			return  $this->method->getStartLine();
		});
	}
	public function getRealStartLine(): int
	{
		return $this->getStartLine() - 1;
	}
	public function getRealEndLine(): int
	{
		return $this->getEndLine() - 1;
	}

	/**
	 * Get the value of endLine
	 */
	public function getEndLine(): int
	{
		return $this->getDyData('EndLine', function () {
			return  $this->method->getEndLine();
		});
	}
	public function getName(): string
	{
		return $this->getDyData('MethodName', function () {
			return  $this->method->getName();
		});
	}
	public function getClassName(): string
	{
		return $this->getDyData('ClassName', function () {
			return  $this->getDeclaringClass()->getName();
		});
	}
	public function isConstructor(): bool
	{
		return $this->getDyData('isConstructor', function () {
			return  $this->method->getName() === '__construct';
		});
	}
	public function isInClass(string $className, $isTrait): bool
	{
		$decl = $this->getDeclaringClass();
		if ($this->isConstructor() || $decl->getName() !== $className)
			return false;
		if ($isTrait/* (new ReflectionClass($className))->isTrait() */)
			return true;
		$isValid = ($decl->getFileName() === $this->method->getFileName() && $decl->getStartLine() <= $this->getStartLine() && $decl->getEndLine() >= $this->getEndLine());
		return $isValid;
	}

	public function getParameters()
	{
		return $this->method->getParameters();
	}
	public function getFileName(): string
	{
		return $this->getDyData('FileName', function () {
			$decl = $this->getDeclaringClass();
			return $decl->getFileName();
		});
	}
	public function getMethodBody(array|null $lines = null): string
	{
		return $this->getDyData('MethodBody', function () use ($lines) {

			$lines = $lines ?? $this->getDescriptor()->getLines();
			$start = $this->getRealStartLine();
			$end = $this->getRealEndLine();
			return implode('', array_slice($lines, $start, $end - $start + 1));
		});
	}
}
