<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class MergeQueryRequestBody
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('QUERY')) {
            $this->mergeBody($request);
        }

        $response = $next($request);

        if (in_array('QUERY', $request->route()?->methods() ?? [], true)) {
            $response->headers->set('Accept-Query', 'application/json, application/x-www-form-urlencoded');
        }

        return $response;
    }

    private function mergeBody(Request $request): void
    {
        $body = $request->isJson() ? $request->json()->all() : $request->request->all();
        $sentTwice = array_keys(array_intersect_key($body, $request->query->all()));

        if ($sentTwice !== []) {
            throw ValidationException::withMessages(
                array_fill_keys($sentTwice, __('Send this parameter either in the address or in the body, not both.')),
            );
        }

        // Controllers read the address with $request->query(), so the body joins it there.
        $request->query->add($body);
    }
}
