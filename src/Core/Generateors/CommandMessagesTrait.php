<?php

declare(strict_types=1);

namespace Maher\CoreTools\Core\Generateors;

use Illuminate\Console\Command;

trait CommandMessagesTrait
{
	protected $outputMessages = [];
	protected bool $showCommandMessages = false;
	/**
	 * Summary of command
	 * @var Command
	 */
	private  $command;
	/**
	 * طباعة المخرجات، تستخدم Command إذا موجودة، وإلا echo
	 */
	protected function line(string $text, $style = null, $verbosity = null): void
	{
		$this->outputMessages[] =  $style ? "<$style>$text</$style>" : $text;
		if ($this->command instanceof Command) {
			$this->command->line($text, $style, $verbosity);
		} elseif($this->showCommandMessages) {
			echo $text . PHP_EOL . " <br>";
		}
	}
	protected function error(string $text): void
	{

		$this->line($text, 'error');
	}
	protected function warn(string $text): void
	{
		$this->line($text, 'warn');
	}
	protected function info(string $text): void
	{
		$this->line($text, 'info');
	}

	/**
	 * Get the output implementation.
	 */
	public function getOutputMessages(): array
	{
		return $this->outputMessages;
	}
}
