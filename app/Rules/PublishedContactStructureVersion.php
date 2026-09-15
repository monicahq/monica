<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\StructureAppliesToEnum;
use App\Models\Structure;
use App\Models\Template;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Override;

/**
 * Accept a structure version an active contact template of the account publishes.
 *
 * The check spans two tables, because whether a template is still offered lives
 * on the template while the version it publishes lives on its structure, which
 * is more than one `exists` rule can say. Four screens ask the same question, so
 * they ask it here.
 */
class PublishedContactStructureVersion implements ValidationRule
{
    public function __construct(private readonly int $accountId) {}

    /** @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail */
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $offered = Structure::query()
            ->where('account_id', $this->accountId)
            ->where('applies_to', StructureAppliesToEnum::Contact)
            ->where('published_version_id', $value)
            ->where('owner_type', Template::class)
            ->whereIn('owner_id', Template::query()->where('is_active', true)->select('id'))
            ->exists();

        if (! $offered) {
            $fail(__('That is not a version a contact in this account can start from.'));
        }
    }
}
