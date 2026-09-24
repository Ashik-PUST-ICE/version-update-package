<?php

namespace Ashik\VersionUpdater;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;

class VersionManager
{
    public function currentBuild(): int
    {
        if (function_exists('getCustomerCurrentBuildVersion')) {
            return (int) getCustomerCurrentBuildVersion();
        }

        $state = $this->state();
        return (int) ($state['build_version'] ?? 1);
    }

    public function currentVersion(): string
    {
        if (function_exists('getOption')) {
            return (string) (getOption('current_version', '1.0'));
        }

        return (string) ($this->state()['current_version'] ?? '1.0');
    }

    public function latestBuild(): int
    {
        return (int) config('version-updater.build_version', 1);
    }

    public function latestVersion(): string
    {
        return (string) config('version-updater.current_version', '1.0');
    }

    public function uploaded(): bool
    {
        return is_file($this->zipPath());
    }

    public function upload($file): void
    {
        $file->storeAs('', 'ashik-update.zip', config('version-updater.disk', 'local'));
    }

    public function deleteUpload(): void
    {
        if (is_file($this->zipPath())) {
            (new Filesystem())->delete($this->zipPath());
        }
    }

    public function apply(): void
    {
        if (!$this->uploaded()) {
            throw new Exception('No Ashik update package has been uploaded.');
        }

        $filesystem = new Filesystem();
        $updatePath = storage_path('app/ashik-update');
        $filesystem->deleteDirectory($updatePath);
        $filesystem->makeDirectory($updatePath, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($this->zipPath()) !== true || !$zip->extractTo($updatePath)) {
            throw new Exception('The Ashik update ZIP could not be opened.');
        }
        $zip->close();

        $notePath = $updatePath . DIRECTORY_SEPARATOR . 'update_note.json';
        $note = json_decode((string) file_get_contents($notePath), true);
        if (!is_array($note) || !isset($note['build_version'], $note['root_path'], $note['code_path'])) {
            throw new Exception('Invalid update_note.json in the Ashik package.');
        }
        if ((int) $note['build_version'] <= $this->currentBuild()) {
            throw new Exception('This update is not newer than the installed version.');
        }

        $root = realpath($updatePath . DIRECTORY_SEPARATOR . $note['root_path']);
        if ($root === false) {
            throw new Exception('The update package root path does not exist.');
        }

        foreach ((array) $note['code_path'] as $relative => $type) {
            $source = $root . DIRECTORY_SEPARATOR . ltrim($relative, '/\\');
            $target = base_path($relative);
            if (str_contains(str_replace('\\', '/', $relative), '..') || !file_exists($source)) {
                throw new Exception('Invalid update path: ' . $relative);
            }
            if ($type === 'file') {
                $filesystem->copy($source, $target);
            } else {
                $filesystem->copyDirectory($source, $target);
            }
        }

        Artisan::call('migrate', ['--force' => true]);
        if (function_exists('setCustomerBuildVersion')) {
            setCustomerBuildVersion((int) $note['build_version']);
            setCustomerCurrentVersion();
        } else {
            file_put_contents($this->statePath(), json_encode([
                'build_version' => (int) $note['build_version'],
                'current_version' => $note['current_version'] ?? $this->latestVersion(),
            ], JSON_PRETTY_PRINT));
        }

        $filesystem->deleteDirectory($updatePath);
        $this->deleteUpload();
    }

    private function zipPath(): string
    {
        return storage_path('app/ashik-update.zip');
    }

    private function statePath(): string
    {
        return storage_path('app/ashik-version.json');
    }

    private function state(): array
    {
        if (!is_file($this->statePath())) {
            return [];
        }
        return json_decode((string) file_get_contents($this->statePath()), true) ?: [];
    }
}
