<?php

namespace Kadiaak\LogViewer;

use Illuminate\Support\Collection;

class LogViewer
{
    /** @var Collection<int, LogFile>|null */
    protected ?Collection $files = null;

    /**
     * Discover every log file matching the configured patterns.
     *
     * @return Collection<int, LogFile>
     */
    public function files(): Collection
    {
        if ($this->files !== null) {
            return $this->files;
        }

        $include = (array) config('log-viewer.include_files', []);
        $exclude = (array) config('log-viewer.exclude_files', []);

        $excluded = collect($exclude)
            ->flatMap(fn ($pattern) => glob($pattern, GLOB_BRACE) ?: [])
            ->map(fn ($p) => realpath($p) ?: $p)
            ->all();

        return $this->files = collect($include)
            ->flatMap(fn ($pattern) => glob($pattern, GLOB_BRACE) ?: [])
            ->map(fn ($p) => realpath($p) ?: $p)
            ->unique()
            ->reject(fn ($p) => in_array($p, $excluded, true))
            ->filter(fn ($p) => is_file($p))
            ->map(fn ($p) => new LogFile($p))
            ->sortByDesc(fn (LogFile $file) => $file->lastModified()?->getTimestamp() ?? 0)
            ->values();
    }

    /**
     * Find a single file by its identifier.
     */
    public function file(string $identifier): ?LogFile
    {
        return $this->files()->first(fn (LogFile $file) => $file->identifier === $identifier);
    }

    /**
     * Find a file or throw a 404.
     */
    public function findOrFail(string $identifier): LogFile
    {
        return $this->file($identifier) ?? abort(404, 'Log file not found.');
    }

    /**
     * Forget the cached file list (useful after a delete).
     */
    public function clearCache(): void
    {
        $this->files = null;
    }
}
