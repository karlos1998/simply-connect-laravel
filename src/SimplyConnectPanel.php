<?php

namespace SimplyConnect\Laravel;

use Closure;
use Illuminate\Http\Request;

final class SimplyConnectPanel
{
    /** @var (Closure(Request): bool)|null */
    private static ?Closure $authUsing = null;

    /** @param Closure(Request): bool $callback */
    public static function auth(Closure $callback): void
    {
        self::$authUsing = $callback;
    }

    public static function check(Request $request): bool
    {
        return self::$authUsing !== null && (self::$authUsing)($request) === true;
    }

    public static function flushAuthorization(): void
    {
        self::$authUsing = null;
    }
}
