<?php

declare(strict_types=1);

namespace Maher\CoreTools\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class MRouteHelper
{
	/**
	 * get All Routes
	 * @template TKey of array-key
	 * @template TValue
	 * @param callable(\Illuminate\Routing\Route)|null $filter
	 * @param (callable(array,int):array<TKey, TValue>)|null $select
	 * @return array
	 */
	public static function getAllRoutes(?callable $filter = null, ?callable $select = null): array
	{
		$routes = Route::getRoutes();
		$result      = [];
		foreach ($routes as $route) {
			// --- Filter callback ---
			if ($filter && !$filter($route)) continue;
			$row = self::getRouteArray($route);
			// --- Select / map callback ---
			if ($select) {
				$mapped = $select($row, count($result));
				if (!m_empty($mapped)) {
					foreach ($mapped as $key => $value) {
						$result[$key] = $value;
					}
				}
			} else {
				$result[] = $row;
			}
		}
		/* if ($filter !== null) {
			$routes = $routes->filter(fn(\Illuminate\Routing\Route $route, $key) => $filter($route))->values();
		}
		$array = $routes->mapWithKeys(function (\Illuminate\Routing\Route $route, $key) {
			$row = self::getRouteArray($route);
			return [
				$row['as'] => $row,
			];
		})->toArray();
		dd($array);
		return $array; */
		return $result;
	}
	public static function getRouteArray(\Illuminate\Routing\Route $route)
	{
		$name = $route->getName();
		
		$action = $route->action;
		$prefix = '';
		if ($name !== null) {
			$nameArr = explode(".", $name);
			$prefix = $nameArr[0];
			$name = end($nameArr);
		
		}
		$uri = $route->uri;
		if (array_key_exists('prefix', $action)) {
			//$uri = Str::replace($action['prefix'], '', $route->uri);
			$prefix = self::getActionPrefix($action);
		}
	
		$method = Str::lower($route->methods[0]);
		$as = $action['as'] ?? null;
		$row = [
			'key' => sprintf("%s.%s", $method, $as),
			'as' => $as,
			'name' => $name,
			'method' => $method,
			'prefix' => $prefix,
			'uri' => empty($uri) ? '/' : $uri,
			'namespace' => $action['namespace'],
			'middleware' => $action['middleware'] ?? null,
			'controller' => $route->getControllerClass(),
		];
		return $row;
	}
	public static function getDoublcateRoutes()
	{
		$routes = collect(Route::getRoutes())->filter(fn($route, $key) => $route->getName() !== null)->values();
		// dd($routes->first() );
		$actions = $routes->mapWithKeys(fn(\Illuminate\Routing\Route $route, $key) => [
			$key => self::getRouteArray($route),
		])->filter(fn($route, $key) => $route['name'] !== $route['as'] && !empty($route['name']))->values()->groupBy([
			'as',
		])->filter(fn($route, $key) => count($route) > 1)->toArray();
		/* $actions = [];
           foreach ($routes as $route) {
               $name = $route->getName();
               $action = $route->action;
               $prefix = '';
               if ($name !== null) {
                   $nameArr = explode(".", $name);
                   $prefix = $nameArr[0];
                   //$name = end($nameArr);
                   // dd($route, $actions);
               }
               $uri = $route->uri;
               if (array_key_exists('prefix', $action)) {
                   $uri = Str::replace($action['prefix'], '', $route->uri);
                   $prefix = self::getActionPrefix($action);
               }
               $actions[] = [
                   'controller' => $action['controller'] ?? null,
                   'namespace' => $action['namespace'],
                   'method' => Str::lower($route->methods[0]),
                   'prefix' => $prefix,
                   'uri' => (empty($uri) ? '/' : $uri),
                   'name' =>  $name,
               ];
           } */
		dd($actions);
		return $actions;
	}
	public static function getActionPrefix($action): mixed
	{
		$prefix = explode('/', $action['prefix']);
		return end($prefix);
	}
}
