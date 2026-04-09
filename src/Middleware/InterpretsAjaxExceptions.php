<?php namespace October\Debugbar\Middleware;

use Request;
use Closure;
use Response;
use Exception;
use Illuminate\Foundation\Application;
use October\Rain\Exception\ErrorHandler;
use October\Rain\Exception\AjaxException;
use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * InterpretsAjaxExceptions captures exceptions from AJAX requests
 * and embeds debugbar data in the response headers
 */
class InterpretsAjaxExceptions
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * __construct
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * handle an incoming request
     */
    public function handle($request, Closure $next)
    {
        /** @var LaravelDebugbar $debugbar */
        $debugbar = $this->app['debugbar'];

        try {
            return $next($request);
        }
        catch (Exception $ex) {
            if (!Request::ajax()) {
                throw $ex;
            }

            $debugbar->addException($ex);

            $message = $ex instanceof AjaxException
                ? $ex->getContents()
                : ErrorHandler::getDetailedMessage($ex);

            return Response::make($message, $this->getStatusCode($ex), $debugbar->getDataAsHeaders());
        }
    }

    /**
     * getStatusCode checks if the exception implements HttpExceptionInterface,
     * or returns a generic 500 error code for a server side error
     */
    protected function getStatusCode($exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            $code = $exception->getStatusCode();
        }
        elseif ($exception instanceof AjaxException) {
            $code = 406;
        }
        else {
            $code = 500;
        }

        return $code;
    }
}
