# Log Viewer for Laravel

A beautiful, fast and self-contained log viewer for your Laravel application — built with Blade, Alpine.js and Tailwind CSS. Inspired by [opcodesio/log-viewer](https://github.com/opcodesio/log-viewer).

- 🎨 Polished UI with light / dark / system themes
- 🔍 Full-text **and** regex search across messages and stack traces
- 🏷️ Filter by log level with live per-level counts
- 📂 Automatic discovery of every `*.log` file (grouped by sub-folder)
- 🧵 Collapsible stack traces & context, one-click copy
- ↕️ Newest / oldest ordering, configurable pagination
- 🔄 Manual & automatic refresh
- 🗑️ Download, clear or delete files from the UI (toggleable)
- 📦 **Zero build step** — compiled assets ship with the package

## Requirements

- PHP 8.2+
- Laravel 10, 11 or 12

## Installation

```bash
composer require kadiaak/log-viewer
```

That's it. Visit **`/log-viewer`** in your browser.

> By default the viewer is only reachable in the `local` environment. To open it
> elsewhere, define a gate (see [Authorization](#authorization)).

### Publishing the config (optional)

```bash
php artisan vendor:publish --tag=log-viewer-config
```

## Authorization

Access is controlled by a `viewLogViewer` gate. Define it in your
`app/Providers/AppServiceProvider.php` (or `AuthServiceProvider`):

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('viewLogViewer', function ($user = null) {
        return in_array(optional($user)->email, [
            'you@example.com',
        ]);
    });
}
```

- If the gate **is defined**, it authorizes every request (in all environments).
- If it is **not defined**, access is restricted to the `local` environment.

You may also attach your own middleware in the config (`route.middleware`), e.g.
`['web', 'auth']`.

## Configuration

The most useful options (see `config/log-viewer.php` for everything):

| Key | Description | Default |
| --- | --- | --- |
| `enabled` | Master switch for the whole package | `true` |
| `route.prefix` | URL prefix for the UI & API | `log-viewer` |
| `route.middleware` | Middleware applied to all routes | `['web']` |
| `include_files` | Glob patterns of files to list | `storage/logs/**/*.log` |
| `exclude_files` | Glob patterns to skip | `[]` |
| `per_page` | Entries per page | `50` |
| `max_log_size` | Skip parsing files larger than this (bytes) | `150 MB` |
| `allow_delete` | Show clear/delete buttons | `true` |
| `allow_download` | Allow downloading files | `true` |
| `theme` | `system`, `light` or `dark` | `system` |

All boolean/scalar options can be driven by environment variables, e.g.
`LOG_VIEWER_ENABLED`, `LOG_VIEWER_PREFIX`, `LOG_VIEWER_THEME`.

## Programmatic usage

```php
use Kadiaak\LogViewer\Facades\LogViewer;

// All discovered files
$files = LogViewer::files();

// A single file + a paginated, filtered scan
$file = LogViewer::files()->first();

$result = $file->scan([
    'query'     => '/SQLSTATE\[\d+\]/', // plain text or /regex/
    'levels'    => ['error', 'critical'],
    'page'      => 1,
    'per_page'  => 50,
    'direction' => 'desc',
]);

// $result['entries'], $result['level_counts'], $result['pagination']
```

## Development

The shipped assets in `resources/dist` are pre-compiled. To work on the UI:

```bash
npm install
npm run dev      # watch CSS + JS  (or `npm run build` for a one-off minified build)
```

Run the test suite:

```bash
composer install
vendor/bin/phpunit
```

Preview the UI against sample data:

```bash
php workbench/sample-logs.php
APP_ENV=local vendor/bin/testbench serve
```

## License

MIT — see [LICENSE.md](LICENSE.md).
