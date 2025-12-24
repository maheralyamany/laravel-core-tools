<?php
if (!function_exists('getValidRequest')) {
	/**
	 * @param Illuminate\Http\Request|null $request
	 * @return Illuminate\Http\Request
	 */
	function getValidRequest($request = null): Illuminate\Http\Request
	{

		return $request ?? request();
	}
}
if (!function_exists('isRunningInConsoleRequest')) {
	/**
	 
	 * @param Illuminate\Contracts\Foundation\Application|null $app
	 * @return bool
	 */
	function isRunningInConsoleRequest($app = null): bool
	{
		$app = $app ?: app();
		return ($app->runningUnitTests() || $app->runningInConsole());
	}
}
if (!function_exists('isAjaxRequest')) {
	/**
	 * @param Illuminate\Http\Request|null $request
	 * 
	 * @return bool
	 */
	function isAjaxRequest($request = null): bool
	{
		$request = getValidRequest($request);
		return $request->ajax() || $request->expectsJson() || isApiRequest($request);
	}
}
if (!function_exists('isApiRequest')) {
	/**
	 * @param Illuminate\Http\Request|null $request
	 * @return bool
	 */
	function isApiRequest($request = null): bool
	{

		return (getValidRequest($request)->is('api/*'));
	}
}
if (!function_exists('hasSession')) {

	/**
	 * check if Request has Session
	 *
	 * @param \Illuminate\Http\Request|null $request
	 * @return bool
	 */
	function hasSession($request = null)
	{
		try {
			return	getValidRequest($request)->hasSession();
		} catch (\Exception $th) {
			report($th);

			return false;
		}
	}
}
if (!function_exists('isStoreOrUpdateRequest')) {
	/**
	 * check is Store Or Update Request
	 *
	 * @param \Illuminate\Http\Request|null $request
	 * @return bool
	 */
	function isStoreOrUpdateRequest($request = null)
	{
		try {
			$request = getValidRequest($request);
			//$request ->ajax()

			return		$request->is('*/update', '*/store');
		} catch (\Exception $th) {
			report($th);
		}
		return false;
	}
}
if (!function_exists('isLivewireRequest')) {
	function isLivewireRequest($request = null)
	{
		$request = getValidRequest($request);
		return $request->is('*/livewire/*') || $request->hasHeader('X-Livewire');
	}
}