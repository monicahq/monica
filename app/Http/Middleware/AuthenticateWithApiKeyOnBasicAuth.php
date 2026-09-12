<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\LogSourceEnum;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithApiKeyOnBasicAuth
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('sanctum')->user();

        if (! $user instanceof User) {
            return $this->challenge();
        }

        $key = $user->currentAccessToken();

        if ($key instanceof PersonalAccessToken && strcasecmp((string) $request->getUser(), $user->email) !== 0) {
            return $this->challenge();
        }

        if (! $key instanceof PersonalAccessToken && ! app()->environment('local')) {
            return $this->challenge();
        }

        Auth::setUser($user);

        $request->attributes->set('log_source', LogSourceEnum::Dav);
        $request->attributes->set('log_source_detail', $key instanceof PersonalAccessToken ? $key->name : null);

        return $next($request);
    }

    private function challenge(): Response
    {
        return response('', Response::HTTP_UNAUTHORIZED, [
            'WWW-Authenticate' => 'Basic realm="'.config('app.name').'", charset="UTF-8"',
        ]);
    }
}
