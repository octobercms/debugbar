<?php declare(strict_types=1);

namespace October\Debugbar\Middleware;

use Closure;
use BackendAuth;
use Fruitcake\LaravelDebugbar\Middleware\InjectDebugbar as BaseMiddleware;

/**
 * InjectDebugbar gates toolbar injection and stored-request persistence
 * on the current backend user being a super user. Non-super-users get
 * the request unchanged and no debugbar data is written to disk.
 */
class InjectDebugbar extends BaseMiddleware
{
    /**
     * handle an incoming request
     */
    public function handle($request, Closure $next)
    {
        $user = BackendAuth::getUser();

        if (!$user || !$user->is_superuser) {
            $this->debugbar->setStorage(null);

            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
