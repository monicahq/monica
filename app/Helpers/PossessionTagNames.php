<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Actions\CreatePossessionTag;
use App\Models\Possession;
use App\Models\PossessionTag;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Tags are given to a possession by name, on the form, through the API and through the MCP server
 * alike. A name matching a tag of the account, ignoring case, is that tag; any other name becomes a
 * new tag of the account.
 */
class PossessionTagNames
{
    public const int MAX_LENGTH = 50;

    /**
     * @param  array<int, mixed>  $names
     * @return list<string>
     */
    public static function normalise(array $names): array
    {
        $kept = [];

        foreach ($names as $name) {
            $name = TextSanitizer::plainText((string) $name);

            if ($name === '') {
                continue;
            }

            if (mb_strlen($name) > self::MAX_LENGTH) {
                throw ValidationException::withMessages([
                    'tags' => __('A tag has at most 50 characters.'),
                ]);
            }

            $kept[mb_strtolower($name)] ??= $name;
        }

        return array_values($kept);
    }

    /** @param  list<string>  $names  as normalise() returns them */
    public static function sync(User $user, Possession $possession, array $names): void
    {
        $existing = $user->account->possessionTags()
            ->get()
            ->keyBy(fn (PossessionTag $tag): string => mb_strtolower($tag->name));

        $ids = [];

        foreach ($names as $name) {
            $tag = $existing->get(mb_strtolower($name)) ?? new CreatePossessionTag(user: $user, name: $name)->execute();

            $ids[] = $tag->id;
        }

        $possession->tags()->sync($ids);
        $possession->unsetRelation('tags');
    }
}
