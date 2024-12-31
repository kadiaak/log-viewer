<?php

namespace Kadiaak\LogViewer\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Kadiaak\LogViewer\LogFile;

/**
 * @method static Collection<int, LogFile> files()
 * @method static LogFile|null file(string $identifier)
 * @method static LogFile findOrFail(string $identifier)
 * @method static void clearCache()
 *
 * @see \Kadiaak\LogViewer\LogViewer
 */
class LogViewer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Kadiaak\LogViewer\LogViewer::class;
    }
}
