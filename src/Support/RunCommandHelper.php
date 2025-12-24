<?php

declare(strict_types=1);

namespace Maher\CoreTools\Support;

use Illuminate\Process\ProcessResult;
use Exception;
use Illuminate\Console\Application;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
class RunCommandHelper
{
    public static function updateComposer(): int
    {
        $process = Process::fromShellCommandline(sprintf('cd %s && composer update ', base_path()));
        $process->setTimeout(3360);
        return $process->run();
    }

    public static function checkRequirments()
    {
        $r = self::installRequireComposer("composer update");
        /* $r = self::installRequireComposer("npm install");
         		$r = self::installRequireComposer("npm run build"); */
        self::runCommand("composer dump-autoload", function ($type, $data) {
            /*  if (is_string($data))
            			 $this->line($data); */
        });
        return $r;
    }

    public static function installRequireComposer($command, $timeout = 3360)
    {
        $process = \Illuminate\Support\Facades\Process::timeout($timeout)->start(static::formatCommand($command), function (string $type, string $output) {
        });
        $result = $process->wait();
        /* $process = Process::fromShellCommandline(sprintf('cd %s && %s ', base_path(), $command));
         		$process->setTimeout($timeout);
         		return	$process->run(); */
        return $result->successful();
    }

    public static function fireCallback(string $type, string|array $output, callable $callback = null)
    {
        if ($callback !== null) {
            if (is_array($output)) {
                foreach ($output as $value) {
                    if (is_array($value)) {
                        self::fireCallback($type, $value, $callback);
                    } else {
                        $callback($type, $value);
                    }
                }
            } else {
                $callback($type, $output);
            }
        }
    }

    /**
     * Undocumented function
     *
     * @param callable(string,string|array)|null $callback callable(string $type, string|array $output)
     * @return ProcessResult
     */
    public static function runCommand(string $command, $callback = null, int $timeout = 3360)
    {
        $process = \Illuminate\Support\Facades\Process::timeout($timeout)->start(static::formatCommand($command), function (string $type, $output) use ($callback) {
            self::fireCallback($type, $output, $callback);
        });
        /*   $result = Process::pipe(function (Pipe $pipe) {
                     $pipe->timeout($timeout)->command($command);
                 }, function (string $type, string $output) use ($callback)  {
                    if ($callback)
         				$callback($type, $output);
                 }); */
        /* $process = Process::fromShellCommandline(sprintf('cd %s && %s ', base_path(), $command));
         		$process->setTimeout($timeout);
         		return $process->run($callback); */
        return $process->wait();
    }

    /**
     * Format the given command as a fully-qualified executable command.
     *
     * @param  string  $string
     */
    public static function formatCommand($string): string
    {
        return sprintf("cd %s && %s ", base_path(), $string);
    }

    public static function getProcess(string $command, int $timeout = 3360): Process
    {
        $formatCommand = static::formatCommand($command);
        return Process::fromShellCommandline($formatCommand, null, null, null, $timeout);
    }

    public static function run($string, $timeout = 0)
    {
        $command = Application::formatCommandString($string);
        try {
            $process = Process::fromShellCommandline($command, base_path());
            $process->setTimeout($timeout);
            $process->mustRun();
            $output = $process->getOutput();
            if (static::isValidOutput($output)) {
                return true;
            }
        } catch (Exception|InvalidArgumentException|LogicException|ProcessFailedException|RuntimeException|Throwable $e) {
            $output = $e->getMessage();
        }
        
        return static::formatOutput($output);
    }

    public static function determineQuote(): string
    {
        return self::isWindows() ? '"' : "'";
    }

    public static function isWindows(): bool
    {
        return str_starts_with(strtoupper(PHP_OS), 'WIN');
    }

    public static function formatOutput($output): ?string
    {
        $output = nl2br($output);
        $output = str_replace(['"', "'"], '', $output);
        return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $output);
    }

    public static function isValidOutput($output)
    {
        $errors = [
            'Content-Type: application/json',
            'CSRF token mismatch',
        ];
        return !Str::contains($output, $errors);
    }

}