<?php

namespace Kadiaak\LogViewer\Logs;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

/**
 * A single parsed log entry.
 */
class LogEntry implements Arrayable
{
    public string $level;

    public string $color;

    public ?CarbonInterface $datetime;

    public string $environment;

    public string $message;

    /** Everything after the first line (stack traces, context, etc.). */
    public string $body;

    /** Byte offset of the entry within the file. */
    public int $position;

    /** A stable identifier for the entry within a file. */
    public string $index;

    public function __construct(array $attributes)
    {
        $this->level = $attributes['level'] ?? Level::NONE;
        $this->color = Level::color($this->level);
        $this->environment = $attributes['environment'] ?? 'production';
        $this->message = $attributes['message'] ?? '';
        $this->body = trim($attributes['body'] ?? '');
        $this->position = $attributes['position'] ?? 0;
        $this->index = (string) ($attributes['index'] ?? $this->position);

        $datetime = $attributes['datetime'] ?? null;

        if ($datetime instanceof CarbonInterface) {
            $this->datetime = $datetime;
        } elseif (is_string($datetime) && $datetime !== '') {
            try {
                $this->datetime = Carbon::parse($datetime);
            } catch (\Throwable) {
                $this->datetime = null;
            }
        } else {
            $this->datetime = null;
        }
    }

    public function hasBody(): bool
    {
        return $this->body !== '';
    }

    /**
     * The full, raw text of the entry (header line + body).
     */
    public function fullText(): string
    {
        return trim($this->message . "\n" . $this->body);
    }

    /**
     * Does the entry match a free-text search query?
     */
    public function matches(string $query): bool
    {
        if ($query === '') {
            return true;
        }

        $haystack = $this->level . ' ' . $this->environment . ' ' . $this->message . ' ' . $this->body;

        // Allow simple regex queries wrapped in slashes, e.g. /SQLSTATE\[\d+\]/
        if (preg_match('#^/.+/[a-z]*$#i', $query)) {
            return (bool) @preg_match($query, $haystack);
        }

        return stripos($haystack, $query) !== false;
    }

    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'level' => $this->level,
            'level_label' => Level::label($this->level),
            'color' => $this->color,
            'environment' => $this->environment,
            'datetime' => $this->datetime?->toIso8601String(),
            'time' => $this->datetime?->format('Y-m-d H:i:s'),
            'message' => $this->message,
            'body' => $this->body,
            'has_body' => $this->hasBody(),
            'position' => $this->position,
        ];
    }
}
