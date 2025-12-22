<?php

namespace Maher\CoreTools\Support;

class StringHelper
{
	public static function removeComments($content, $commentTypes = [
		'single',
		'multi',
		'html',
	])
	{
		$patterns = [];
		if (in_array('single', $commentTypes)) {
			$patterns['/\/\/.*$/m'] = '';
		}

		if (in_array('multi', $commentTypes)) {
			$patterns['/\/\*[\s\S]*?\*\//'] = '';
		}

		if (in_array('html', $commentTypes)) {
			$patterns['/<!--[\s\S]*?-->/'] = '';
		}

		foreach ($patterns as $pattern => $replacement) {
			$content = preg_replace($pattern, $replacement, $content);
		}

		// Clean up extra newlines
		$content = preg_replace('/\n\s*\n/', "\n", $content);
		return trim($content);
	}

	/**
	 * Determine if a given string contains any array values.
	 *
	 * @param  string  $haystack
	 * @param  iterable<string>  $needles
	 * @param  bool  $ignoreCase
	 * @return bool
	 */
	public static function containsAny($haystack, $needles, $ignoreCase = false)
	{
		if (m_empty($haystack) || m_empty($needles))
			return false;
		foreach ($needles as $needle) {

			if (\Str::contains($haystack, $needle, $ignoreCase))
				return true;
		}
		return false;
	}
}
