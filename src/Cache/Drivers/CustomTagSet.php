<?php

declare(strict_types=1);

namespace Maher\CoreTools\Cache\Drivers;

use Maher\CoreTools\Cache\Store\TaggedCustomCacheStore;

class CustomTagSet extends \Illuminate\Cache\TagSet
{
	protected $hasTagNames = false;
	/**
	 * Create a new TagSet instance.
	 *
	 * @param  \Maher\CoreTools\Cache\Store\TaggedCustomCacheStore  $store
	 * @param  array  $names
	 * @return void
	 */
	public function __construct(TaggedCustomCacheStore $store, array $names = [])
	{
		parent::__construct($store, $names);
		$this->hasTagNames = count($this->names) > 0;
	}
	/**
	 * Get the tag identifier key for a given tag.
	 *
	 * @param  string  $name
	 * @return string
	 */
	public function tagKey($name)
	{
		if ($this->hasTagNames)
			return implode('-', $this->names) . ':' . $name;
		return parent::tagKey($name);
	}
}
