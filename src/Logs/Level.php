<?php

namespace Kadiaak\LogViewer\Logs;

/**
 * The canonical PSR-3 log levels, plus helpers for displaying them.
 */
class Level
{
    public const EMERGENCY = 'emergency';
    public const ALERT = 'alert';
    public const CRITICAL = 'critical';
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const NOTICE = 'notice';
    public const INFO = 'info';
    public const DEBUG = 'debug';
    public const NONE = 'none';

    /**
     * All known levels, ordered from most to least severe.
     *
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::EMERGENCY,
            self::ALERT,
            self::CRITICAL,
            self::ERROR,
            self::WARNING,
            self::NOTICE,
            self::INFO,
            self::DEBUG,
            self::NONE,
        ];
    }

    /**
     * Normalise an arbitrary string into a known level.
     */
    public static function from(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, self::all(), true) ? $value : self::NONE;
    }

    /**
     * A tailwind-ish colour key used by the UI for badges.
     */
    public static function color(string $level): string
    {
        return match (self::from($level)) {
            self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR => 'red',
            self::WARNING => 'amber',
            self::NOTICE, self::INFO => 'blue',
            self::DEBUG => 'green',
            default => 'gray',
        };
    }

    /**
     * Human friendly label.
     */
    public static function label(string $level): string
    {
        return ucfirst(self::from($level));
    }
}
