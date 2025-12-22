<?php
namespace Maher\CoreTools\Support;

class HelpersLoader {
    public static function load(string $path): void {
        foreach (glob($path.'/*.php') as $file) {
            require_once $file;
        }
    }
}