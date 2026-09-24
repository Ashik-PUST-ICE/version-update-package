<?php

namespace Ashik\VersionUpdater\Support;

use Throwable;

class InstallLogger
{
    public static function write(string $message, array $context = []): void
    {
        $line = '[' . now()->format('Y-m-d H:i:s') . '] ' . $message;
        if ($context !== []) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }
        $line .= PHP_EOL;

        @file_put_contents(storage_path('logs/install.log'), $line, FILE_APPEND | LOCK_EX);
    }

    public static function exception(Throwable $exception): void
    {
        self::write('Installation failed', [
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }
}
