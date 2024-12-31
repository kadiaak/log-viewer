<?php

namespace Kadiaak\LogViewer;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Kadiaak\LogViewer\Logs\Level;
use Kadiaak\LogViewer\Logs\LogParser;

class LogFile implements Arrayable
{
    public string $path;

    public string $name;

    public string $identifier;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->name = basename($path);
        $this->identifier = sha1($path);
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    public function size(): int
    {
        return $this->exists() ? (int) filesize($this->path) : 0;
    }

    public function humanSize(): string
    {
        $bytes = $this->size();
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $i = min($i, count($units) - 1);

        return round($bytes / (1024 ** $i), $i ? 2 : 0) . ' ' . $units[$i];
    }

    public function lastModified(): ?Carbon
    {
        if (! $this->exists()) {
            return null;
        }

        return Carbon::createFromTimestamp(filemtime($this->path));
    }

    /**
     * The folder this file sits in, relative to the logs path, for grouping.
     */
    public function subFolder(): string
    {
        $base = storage_path('logs');
        $dir = dirname($this->path);

        if (str_starts_with($dir, $base)) {
            $dir = trim(substr($dir, strlen($base)), DIRECTORY_SEPARATOR);
        }

        return $dir;
    }

    public function isTooLarge(): bool
    {
        $max = (int) config('log-viewer.max_log_size', 0);

        return $max > 0 && $this->size() > $max;
    }

    /**
     * Scan the file and return a structured, paginated result.
     *
     * @param  array{query?: string, levels?: string[], page?: int, per_page?: int, direction?: string}  $options
     */
    public function scan(array $options = []): array
    {
        $query = trim((string) ($options['query'] ?? ''));
        $levels = array_filter((array) ($options['levels'] ?? []));
        $page = max(1, (int) ($options['page'] ?? 1));
        $perPage = max(1, (int) ($options['per_page'] ?? config('log-viewer.per_page', 50)));
        $direction = ($options['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $counts = array_fill_keys(Level::all(), 0);
        $matched = [];
        $totalMatchingSearch = 0;

        if (! $this->isTooLarge()) {
            $parser = new LogParser();

            foreach ($parser->parseFile($this->path) as $entry) {
                if (! $entry->matches($query)) {
                    continue;
                }

                $counts[$entry->level] = ($counts[$entry->level] ?? 0) + 1;
                $totalMatchingSearch++;

                if ($levels === [] || in_array($entry->level, $levels, true)) {
                    $matched[] = $entry;
                }
            }
        }

        if ($direction === 'desc') {
            $matched = array_reverse($matched);
        }

        $total = count($matched);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $slice = array_slice($matched, ($page - 1) * $perPage, $perPage);

        return [
            'entries' => array_map(fn ($e) => $e->toArray(), $slice),
            'level_counts' => $counts,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total ? (($page - 1) * $perPage) + 1 : 0,
                'to' => min($page * $perPage, $total),
            ],
            'total_matching_search' => $totalMatchingSearch,
        ];
    }

    public function download(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return response()->download($this->path, $this->name);
    }

    public function clear(): bool
    {
        if (! $this->exists()) {
            return false;
        }

        return file_put_contents($this->path, '') !== false;
    }

    public function delete(): bool
    {
        if (! $this->exists()) {
            return false;
        }

        return @unlink($this->path);
    }

    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'name' => $this->name,
            'sub_folder' => $this->subFolder(),
            'size' => $this->size(),
            'human_size' => $this->humanSize(),
            'last_modified' => $this->lastModified()?->toIso8601String(),
            'last_modified_human' => $this->lastModified()?->diffForHumans(),
            'is_too_large' => $this->isTooLarge(),
        ];
    }
}
