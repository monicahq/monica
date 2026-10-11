<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;
use Override;

class UploadsAreUnavailable implements ValidationRule
{
    /**
     * Asked even when nothing was sent, so a required field says why it cannot be filled in rather
     * than only that it is required.
     */
    public bool $implicit = true;

    public function __construct(
        private readonly bool $required,
    ) {}

    /** @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail */
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile && ! $this->required) {
            return;
        }

        $fail(__('Uploads are unavailable on this account for now.'));
    }
}
