<?php

namespace Kadiaak\LogViewer\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class AssetController
{
    /**
     * Serve a bundled CSS/JS asset straight from the package, so users never
     * have to run `vendor:publish` for assets to work.
     */
    public function __invoke(string $file): BinaryFileResponse|Response
    {
        $allowed = [
            'app.css' => 'text/css',
            'app.js' => 'application/javascript',
        ];

        if (! isset($allowed[$file])) {
            abort(404);
        }

        $path = __DIR__ . '/../../../resources/dist/' . $file;

        if (! is_file($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => $allowed[$file],
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
