<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Check the token the Turnstile widget put in the form against Cloudflare. A
 * call that fails for any reason fails the rule, so a challenge that cannot be
 * verified never counts as passed.
 */
class Turnstile implements ValidationRule
{
    /**
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('turnstile.secret_key'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        if ($response->failed() || $response->json('success') !== true) {
            $fail(__('The human check failed. Please try again.'));
        }
    }
}
