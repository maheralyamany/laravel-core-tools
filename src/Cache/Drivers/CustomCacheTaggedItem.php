<?php

declare(strict_types=1);

namespace Maher\CoreTools\Cache\Drivers;

use Illuminate\Support\Facades\File;
use Maher\CoreTools\Cache\Store\TaggedCustomCacheStore;

class CustomCacheTaggedItem extends \Illuminate\Cache\TaggedCache
{
	/* use RetrievesMultipleKeys {
		putMany as putManyAlias;
	} */
	/**
	 * Summary of store
	 * @var \Maher\CoreTools\Cache\Store\TaggedCustomCacheStore
	 */
	protected $store;
	/**
	 * The tag set instance.
	 *
	 * @var \Maher\CoreTools\Cache\Drivers\CustomTagSet
	 */
	protected $tags;



	/**
	 * Create a new tagged cache instance.
	 *
	 * @param  \Maher\CoreTools\Cache\Store\TaggedCustomCacheStore  $store
	 * @param  array  $names
	 * @return void
	 */
	public function __construct(TaggedCustomCacheStore $store, array $names)
	{

		$this->store = $store;
		$tags = new CustomTagSet($this->store, $names);
		parent::__construct($store, $tags);
		$this->tags = $tags;
	}

	protected function getTagsNames()
	{
		return $this->tags->getNames();
	}
	protected function getTagsName()
	{
		return implode('-', $this->getTagsNames());
	}
	protected function tagKey($key)
	{
		return $this->getTagsName() . ':' . $key;
	}
	protected function itemKey($key)
	{
		return $this->getTagsName() . ':' . $key;
	}

	protected function tagDir()
	{
		return $this->getTagsName();
	}



	/**
	 * {@inheritdoc}
	 *
	 * @return bool
	 */
	public function clear(): bool
	{
		return $this->flush();
	}
	public function flush()
	{
		$dirPath = $this->store->basePath . DIRECTORY_SEPARATOR . $this->tagDir();
		if (File::exists($dirPath)) {
			File::deleteDirectory($dirPath);
		}
		return $this->store->flush();
	}
}
