<?php

namespace Kadiaak\LogViewer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kadiaak\LogViewer\LogViewer;

class LogController
{
    public function __construct(protected LogViewer $viewer)
    {
    }

    /**
     * Return a paginated, filtered list of entries for a given file.
     */
    public function index(Request $request, string $identifier): JsonResponse
    {
        $file = $this->viewer->findOrFail($identifier);

        $result = $file->scan([
            'query' => (string) $request->query('query', ''),
            'levels' => array_filter((array) $request->query('levels', [])),
            'page' => (int) $request->query('page', 1),
            'per_page' => (int) $request->query('per_page', config('log-viewer.per_page', 50)),
            'direction' => (string) $request->query('direction', 'desc'),
        ]);

        return response()->json(array_merge($result, [
            'file' => $file->toArray(),
        ]));
    }
}
