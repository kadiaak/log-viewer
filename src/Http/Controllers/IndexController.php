<?php

namespace Kadiaak\LogViewer\Http\Controllers;

use Illuminate\Contracts\View\View;

class IndexController
{
    public function __invoke(): View
    {
        return view('log-viewer::index', [
            'config' => [
                'route_prefix' => config('log-viewer.route.prefix', 'log-viewer'),
                'per_page' => (int) config('log-viewer.per_page', 50),
                'theme' => config('log-viewer.theme', 'system'),
                'allow_delete' => (bool) config('log-viewer.allow_delete', true),
                'allow_download' => (bool) config('log-viewer.allow_download', true),
                'back_to_system_url' => config('log-viewer.back_to_system_url'),
                'back_to_system_label' => config('log-viewer.back_to_system_label') ?: config('app.name', 'Application'),
                'app_name' => config('app.name', 'Laravel'),
            ],
        ]);
    }
}
