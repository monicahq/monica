<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InsertServerGenerationTime
{
    public const string MARKER = '<!--server-generation-time-->';

    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $content = $response->getContent();

        if (! is_string($content) || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }

        $position = strrpos($content, self::MARKER);

        if ($position === false) {
            return $response;
        }

        $started = defined('LARAVEL_START') ? LARAVEL_START : (float) $request->server('REQUEST_TIME_FLOAT', microtime(true));
        $milliseconds = (int) round((microtime(true) - $started) * 1000);

        $response->setContent(substr_replace($content, "{$milliseconds} ms", $position, strlen(self::MARKER)));
        $response->headers->set('Server-Timing', "app;dur={$milliseconds}");

        return $response;
    }
}
