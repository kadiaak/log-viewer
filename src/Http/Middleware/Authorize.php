<?php

namespace Kadiaak\LogViewer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Gate access to the log viewer.
 *
 * If you define a `viewLogViewer` gate in your application it will be used to
 * authorize every request. Otherwise access is only granted in the `local`
 * environment, so the viewer is never accidentally exposed in production.
 */
class Authorize
{
    public function handle(Request $request, Closure $next)
    {
        if (Gate::has('viewLogViewer')) {
            if (! Gate::allows('viewLogViewer', [$request])) {
                abort(403, 'You are not allowed to access the log viewer.');
            }

            return $next($request);
        }

        abort_unless(app()->environment('local'), 403, 'The log viewer is restricted to the local environment. Define a "viewLogViewer" gate to grant access elsewhere.');

        return $next($request);
    }
}
