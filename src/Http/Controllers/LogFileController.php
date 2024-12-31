<?php

namespace Kadiaak\LogViewer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Kadiaak\LogViewer\LogViewer;

class LogFileController
{
    public function __construct(protected LogViewer $viewer)
    {
    }

    /**
     * List all available log files.
     */
    public function index(): JsonResponse
    {
        $files = $this->viewer->files()
            ->map(fn ($file) => $file->toArray())
            ->groupBy('sub_folder')
            ->map(fn ($group, $folder) => [
                'folder' => $folder,
                'files' => $group->values(),
            ])
            ->values();

        return response()->json([
            'folders' => $files,
        ]);
    }

    /**
     * Download a log file.
     */
    public function download(string $identifier)
    {
        abort_unless((bool) config('log-viewer.allow_download', true), 403);

        return $this->viewer->findOrFail($identifier)->download();
    }

    /**
     * Empty a log file's contents.
     */
    public function clear(string $identifier): JsonResponse
    {
        abort_unless((bool) config('log-viewer.allow_delete', true), 403);

        $ok = $this->viewer->findOrFail($identifier)->clear();

        return response()->json(['success' => $ok]);
    }

    /**
     * Delete a log file.
     */
    public function destroy(string $identifier): JsonResponse
    {
        abort_unless((bool) config('log-viewer.allow_delete', true), 403);

        $ok = $this->viewer->findOrFail($identifier)->delete();
        $this->viewer->clearCache();

        return response()->json(['success' => $ok]);
    }
}
