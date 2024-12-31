<?php

namespace Kadiaak\LogViewer\Logs;

use Generator;

/**
 * Turns a raw Laravel log file into a stream of {@see LogEntry} objects.
 *
 * The default Laravel/Monolog line format looks like:
 *
 *   [2024-01-15 10:30:45] local.ERROR: Something went wrong {"exception":"..."}
 *
 * Anything that follows a header line (until the next header) is treated as the
 * entry body — typically a stack trace or JSON context.
 */
class LogParser
{
    /**
     * Matches the header line of a log entry and captures its parts.
     */
    public const HEADER_REGEX = '/^\[(?<datetime>\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:[\+-]\d{2}:?\d{2}|Z)?)\]\s*(?<env>[^\s.]+)\.(?<level>[A-Za-z]+):\s?(?<message>.*?)\s*$/';

    /**
     * Read a file and yield each entry lazily.
     *
     * @return Generator<LogEntry>
     */
    public function parseFile(string $path): Generator
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return;
        }

        try {
            yield from $this->parseStream($handle);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     * @return Generator<LogEntry>
     */
    public function parseStream($handle): Generator
    {
        $current = null;
        $bodyLines = [];
        $position = 0;
        $offset = 0;
        $index = 0;

        while (($line = fgets($handle)) !== false) {
            $lineStart = $offset;
            $offset += strlen($line);
            $line = rtrim($line, "\r\n");

            if (preg_match(self::HEADER_REGEX, $line, $m)) {
                // Flush the entry we were building.
                if ($current !== null) {
                    yield $this->makeEntry($current, $bodyLines, $position, $index++);
                }

                $current = $m;
                $bodyLines = [];
                $position = $lineStart;

                continue;
            }

            if ($current !== null) {
                $bodyLines[] = $line;
            }
        }

        if ($current !== null) {
            yield $this->makeEntry($current, $bodyLines, $position, $index);
        }
    }

    private function makeEntry(array $header, array $bodyLines, int $position, int $index): LogEntry
    {
        return new LogEntry([
            'datetime' => $header['datetime'] ?? null,
            'environment' => $header['env'] ?? 'production',
            'level' => Level::from($header['level'] ?? Level::NONE),
            'message' => trim($header['message'] ?? ''),
            'body' => implode("\n", $bodyLines),
            'position' => $position,
            'index' => $position, // byte offset makes a stable, unique index
        ]);
    }
}
