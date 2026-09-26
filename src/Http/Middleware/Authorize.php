<?php

namespace SimplyConnect\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SimplyConnect\Laravel\SimplyConnectPanel;
use Symfony\Component\HttpFoundation\Response;

final class Authorize
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(SimplyConnectPanel::check($request), 403);

        return $next($request);
    }
}
