<?php

use Maher\CoreTools\Support\GoogleTranslation;
use Maher\CoreTools\Support\TranslationManager;

if (!function_exists('sm_trans')) {
	/**
	 * Translate the given message.
	 *
	 * @param  string|null  $key
	 * @param  array  $replace
	 * @param  string|null  $locale
	 * @return \Illuminate\Contracts\Translation\Translator|string|array|null
	 */
	function sm_trans($key = null, $replace = [], $locale = null)
	{
		return TranslationManager::transWithFallback($key, $replace, $locale);
	}
}

if (!function_exists('googleTranslator')) {
	/**
	 * Translate (array of sentences) or single sentence
	 * @param mixed $text
	 * @param string $to
	 * @param string $from
	 * @return array|string
	 */
	function googleTranslator(
		mixed $text,
		string $to = 'ar',
		string $from = 'auto'
	): array|string {
		return GoogleTranslation::dyTranslate($text, $to, $from);
	}
}