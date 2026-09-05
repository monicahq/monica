<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;
use Override;

class Turnstile implements ValidationRule
{
    /** @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail */
    #[Override]
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
