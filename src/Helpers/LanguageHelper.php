<?php

use Maher\CoreTools\Support\TranslationManager;

if (!function_exists('m_trans')) {
	/**
	 * Translate the given message.
	 *
	 * @param  string|null  $key
	 * @param  array  $replace
	 * @param  string|null  $locale
	 * @return \Illuminate\Contracts\Translation\Translator|string|array|null
	 */
	function m_trans($key = null, $replace = [], $locale = null)
	{
		return TranslationManager::transWithFallback($key, $replace, $locale);
	}
}