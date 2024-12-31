<?php

namespace Kadiaak\LogViewer\Tests;

use Kadiaak\LogViewer\LogViewerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected string $logsDir;

    protected function setUp(): void
    {
        $this->logsDir = sys_get_temp_dir() . '/kadiaak-log-viewer-tests-' . getmypid();
        $this->cleanLogsDir();
        @mkdir($this->logsDir, 0777, true);

        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [LogViewerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.env', 'local');
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        // Isolate discovery to a dedicated temp directory so tests never read
        // the real (or testbench) storage path.
        $app['config']->set('log-viewer.include_files', [
            $this->logsDir . '/**/*.log',
            $this->logsDir . '/*.log',
        ]);

        \Illuminate\Support\Facades\Gate::define('viewLogViewer', fn ($user = null) => true);
    }

    protected function makeLog(string $name, string $contents): string
    {
        $path = $this->logsDir . DIRECTORY_SEPARATOR . $name;
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, $contents);

        return $path;
    }

    protected function tearDown(): void
    {
        $this->cleanLogsDir();

        parent::tearDown();
    }

    private function cleanLogsDir(): void
    {
        if (! is_dir($this->logsDir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->logsDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }

        @rmdir($this->logsDir);
    }
}
