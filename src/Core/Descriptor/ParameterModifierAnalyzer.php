<?php

declare(strict_types=1);

namespace Maher\CoreTools\Core\Descriptor;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Reflection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use SplFileObject;

class ParameterModifierAnalyzer
{

	public static function getMethodBodyLines(ReflectionMethod $method, array|null $lines = null): array
	{
		$lines = $lines ?? file($method->getFileName());


		$start = $method->getStartLine() - 1;
		$end = $method->getEndLine() - 1;
		$bodyLines = array_slice($lines, $start, $end - $start + 1);
		return $bodyLines;
	}
	/**
	 * 
	 * @param ReflectionMethod $ref
	
	 * @param array<int,array{defvalue: string|null, modifier: string|null, name: string, paramStr: string, type: string, variadic: string|null}>|null  $paramsInfo
	 * @return string|null
	 */
	public static function replaceReflectionMethodSignature(ReflectionMethod $ref, array $paramsInfo): string|null
	{
		$method = $ref->getName();
		// بناء التوقيع الجديد

		$paramsInfo = $paramsInfo ?? self::getMethodParameters($ref);
		/* $paramsInfo = $validToReplace($paramsInfo);
		if (is_null($paramsInfo))
			return null; */
		$params = Arr::mapWithKeys($paramsInfo, fn($p, $k) => [$k => $p['paramStr']]);


		$file = $ref->getFileName();
		$start = $ref->getStartLine() - 1; // index يبدأ من 0
		$end = $ref->getEndLine();
		$realStart = $start;
		$realEnd = $end - 1;
		$info = self::analyzeMethod($ref);
		// قراءة الملف
		$lines = file($file);



		$body = $info['body'];
		$body = rtrim($body, "\n}") . "\n"; // إزالة القوس الأخير فقط


		//dd($info, $body);
		$paramsString = implode(', ', $params);

		// Access modifier + static للدالة نفسها
		$access = $ref->isPublic() ? 'public' : ($ref->isProtected() ? 'protected' : 'private');
		$static = $ref->isStatic() ? ' static' : '';

		$signature = $info['signature'];
		$newSignature = "$access$static function {$method}($paramsString)";
		$contents = implode("", $lines);
		$contents = Str::replace($signature, $newSignature, $contents);
		// توليد الكود النهائي
		//$newMethodCode = $newSignature . "\n{\n$body}\n}\n";
		//dd($start, $end, $newSignature, $newMethodCode, $contents);
		// استبدال السطور القديمة
		//array_splice($lines, $start, $end - $start, $newMethodCode);

		//file_put_contents($file, implode("", $lines));
		file_put_contents($file, $contents);
		return $newSignature;
	}
	public static function analyzeMethod(ReflectionMethod $method): array
	{
		$source = file($method->getFileName());
		$startLine = $method->getStartLine() - 1;
		$endLine = $method->getEndLine() - 1;

		$methodCode = implode('', array_slice($source, $startLine, $endLine - $startLine + 1));

		// Find the method body
		$bracePos = strpos($methodCode, '{');

		if ($bracePos === false) {
			return [
				'name' => $method->getName(),
				'signature' => trim($methodCode),
				'body' => '',
				'is_abstract' => $method->isAbstract(),
				'modifiers' => Reflection::getModifierNames($method->getModifiers()),
				'start_line' => $method->getStartLine(),
				'end_line' => $method->getEndLine()
			];
		}

		// Extract signature and body
		$signature = trim(substr($methodCode, 0, $bracePos));

		// Find matching closing brace
		$braceCount = 0;
		$bodyEnd = $bracePos;

		for ($i = $bracePos; $i < strlen($methodCode); $i++) {
			if ($methodCode[$i] === '{') {
				$braceCount++;
			} elseif ($methodCode[$i] === '}') {
				$braceCount--;
				if ($braceCount === 0) {
					$bodyEnd = $i;
					break;
				}
			}
		}

		$body = trim(substr($methodCode, $bracePos + 1, $bodyEnd - $bracePos - 1));

		return [
			'name' => $method->getName(),
			'signature' => $signature,
			'body' => $body,
			'modifiers' => Reflection::getModifierNames($method->getModifiers()),
			'return_type' => $method->getReturnType()?->getName(),
			'parameter_count' => $method->getNumberOfParameters(),
			'start_line' => $method->getStartLine(),
			'end_line' => $method->getEndLine(),
			'body_line_count' => substr_count($body, "\n") + 1
		];
	}
	public static function splitWithTokens(string $className, string $methodName): array
	{
		$method = new ReflectionMethod($className, $methodName);
		$tokens = token_get_all(file_get_contents($method->getFileName()));

		$inMethod = false;
		$braceCount = 0;
		$signatureTokens = [];
		$bodyTokens = [];
		$currentLine = 0;
		$methodStartLine = $method->getStartLine();
		$methodEndLine = $method->getEndLine();

		foreach ($tokens as $token) {
			if (is_array($token)) {
				$currentLine = $token[2];

				// Check if we're in the target method
				if ($currentLine == $methodStartLine && !$inMethod) {
					$inMethod = true;
				}

				if ($inMethod) {
					if ($braceCount == 0 && $token[0] != T_WHITESPACE) {
						if ($token[0] == '{') {
							$braceCount++;
							continue; // Skip the opening brace
						}
						$signatureTokens[] = $token[1];
					} elseif ($braceCount > 0) {
						$bodyTokens[] = is_array($token) ? $token[1] : $token;
					}
				}
			} else {
				if ($inMethod) {
					if ($token == '{') {
						$braceCount++;
						if ($braceCount == 1) continue; // Skip first opening brace
					} elseif ($token == '}') {
						$braceCount--;
						if ($braceCount == 0) {
							break; // Reached end of method
						}
					}

					if ($braceCount > 0) {
						$bodyTokens[] = $token;
					}
				}
			}

			if ($currentLine > $methodEndLine) {
				break;
			}
		}

		return [
			'signature' => trim(implode('', $signatureTokens)),
			'body' => trim(implode('', $bodyTokens)),
			'modifiers' => Reflection::getModifierNames($method->getModifiers()),
			'return_type' => $method->getReturnType()?->getName(),
			//'parameters' => self::extractParameters($method)
		];
	}
	public static function splitMethodSafely(ReflectionMethod $method): array
	{
		$file = new SplFileObject($method->getFileName());
		$file->seek($method->getStartLine() - 1);

		$code = '';
		$lineCount = $method->getEndLine() - $method->getStartLine() + 1;

		for ($i = 0; $i < $lineCount; $i++) {
			$code .= $file->current();
			$file->next();
		}

		// Handle different brace styles
		$patterns = [
			// Standard: function() { ... }
			'/^(.*?)\{\s*(.*?)\s*\}$/s' => 'standard',
			// One-liner: function() { return; }
			'/^(.*?)\{\s*(.*?;)\s*\}$/' => 'one_liner',
			// Multiple statements on one line
			'/^(.*?)\{\s*(.*?)\s*\}$/m' => 'multiline'
		];

		foreach ($patterns as $pattern => $type) {
			if (preg_match($pattern, $code, $matches)) {
				return [
					'signature' => trim($matches[1]),
					'body' => trim($matches[2]),
					'type' => $type
				];
			}
		}

		// Fallback: simple split on first '{'
		$bracePos = strpos($code, '{');
		if ($bracePos !== false) {
			return [
				'signature' => trim(substr($code, 0, $bracePos)),
				'body' => trim(substr($code, $bracePos + 1, -1)),
				'type' => 'fallback'
			];
		}

		return [
			'signature' => trim($code),
			'body' => '',
			'type' => 'abstract'
		];
	}
	public static function getPromotedParameterInfo(ReflectionClass $reflectionClass): array
	{

		$constructor = $reflectionClass->getConstructor();
		$info = [];

		foreach ($constructor->getParameters() as $param) {
			if ($param->isPromoted()) {
				$property = $reflectionClass->getProperty($param->getName());
				$info[$param->getName()] = [
					'parameter_name' => $param->getName(),
					'property_modifiers' => $property->getModifiers(),
					'modifier_names' => Reflection::getModifierNames($property->getModifiers()),
					'is_private' => $property->isPrivate(),
					'is_protected' => $property->isProtected(),
					'is_public' => $property->isPublic(),
					'is_readonly' => $property->isReadOnly(),
					'is_static' => $property->isStatic(),
					'type' => $property->getType()?->getName(),
					'default_value' => $param->isDefaultValueAvailable()
						? $param->getDefaultValue()
						: null,
				];
			}
		}

		return $info;
	}
	/**
	 * get method Parameters
	 * @param ReflectionMethod $ref
	 * @param callable(array{defvalue: string|null, modifier: string|null, name: string, paramStr: string, type: string, variadic: string|null}):bool $checkParamExists
	 * @return array<int,array{defvalue: string|null, modifier: string|null, name: string, paramStr: string, type: string, variadic: string|null}>|null
	 */
	public static function getMethodParameters(ReflectionMethod $ref, ?callable $checkParamExists = null)
	{
		// بناء التوقيع الجديد
		$params = [];
		$reflectionClass = $ref->getDeclaringClass();
		foreach ($ref->getParameters() as $p) {
			$paramStr = '';
			$_paramStr = [];
			$param = [
				'name' => $p->getName(),
				'type' => '',
				'modifier' => null,
				'variadic' => null,
				'defvalue' => null,
				'is_readonly' => false,
				'is_static' => false,
				'paramStr' => '',
			];
			/*  private string $title,
        public readonly float $price,
        protected static int $count = 0 */
			// Access modifier for promoted properties
			if ($p->isPromoted()) {


				$property = $reflectionClass->getProperty($p->getName());
				/* $info = [
					'parameter_name' => $p->getName(),
					'property_modifiers' => $property->getModifiers(),
					'modifier_names' => Reflection::getModifierNames($property->getModifiers()),
					'is_private' => $property->isPrivate(),
					'is_protected' => $property->isProtected(),
					'is_public' => $property->isPublic(),
					'is_readonly' => $property->isReadOnly(),
					'is_static' => $property->isStatic(),
					'type' => $property->getType()?->getName(),
					'default_value' => $p->isDefaultValueAvailable()
						? $p->getDefaultValue()
						: null,
				]; */
				$param['modifier']  = $property->isPublic() ? 'public' : ($property->isProtected() ? 'protected' : ($property->isPrivate() ? 'private' : null));
				//$static = $property->isStatic() ? ' static' : '';
				$param['is_static'] = $property->isStatic();
				$param['is_readonly'] = $property->isReadOnly();
				/* $mod = $p->getModifiers();
				if ($mod & ReflectionProperty::IS_PUBLIC)
					$param['modifier'] = 'public';
				elseif ($mod & ReflectionProperty::IS_PROTECTED)
					$param['modifier'] = 'protected';
				elseif ($mod & ReflectionProperty::IS_PRIVATE)
					$param['modifier'] = 'private'; */
			}
			if (!empty($param['modifier'])) {
				$_paramStr[] = $param['modifier'];
				$paramStr = $param['modifier'] . ' ' . $paramStr;
			}
			if ($param['is_readonly']) {
				$_paramStr[] = 'readonly';
			}
			// type
			$paramType = '';
			if ($p->hasType()) {
				$type = $p->getType();
				if ($type instanceof ReflectionNamedType) {
					$paramType = ($type->allowsNull() && !$type->isBuiltin() ? '?' : '') . $type->getName();
					$paramStr .= $paramType . ' ';
				} else {
					$paramType = (string)$type;
					$paramStr .= $paramType . ' ';
				}
			}
			$_paramStr[] = $paramType;
			$param['type'] = $paramType;



			// variadic
			if ($p->isVariadic()) {
				$param['variadic'] = '...';
				$paramStr .= '...';
				$_paramStr[] = "...\$" . $p->getName();
			} else {
				$_paramStr[] = '$' . $p->getName();
			}

			// name
			$paramStr .= '$' . $p->getName();

			// default
			if ($p->isOptional() && !$p->isVariadic()) {
				$defvalue = var_export($p->getDefaultValue(), true);
				$paramStr .= ' = ' . $defvalue;
				$_paramStr[] = ' = ' . $defvalue;
				$param['defvalue'] = $defvalue;
			}
			$param['paramStr'] = implode(" ", $_paramStr);
			if ($checkParamExists != null) {
				if ($checkParamExists($param)) {
					
					return null;
				}
			}
			$params[] = $param;
		}
		return $params;
	}
}