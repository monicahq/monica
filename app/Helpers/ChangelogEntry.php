<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ChangelogEntryTypeEnum;
use Carbon\CarbonImmutable;

/**
 * One change described in a file of `changelog/`. Its identity is the id written in the file,
 * never the file name, so comments, votes and seen marks survive a rename.
 */
readonly class ChangelogEntry
{
    /** @param  list<ChangelogScreenshot>  $screenshots */
    public function __construct(
        public string $id,
        public string $title,
        public ChangelogEntryTypeEnum $type,
        public ?string $area,
        public CarbonImmutable $publishedAt,
        public ?string $link,
        public string $body,
        public string $file,
        public array $screenshots = [],
    ) {}

    public function day(): string
    {
        return $this->publishedAt->format('Y-m-d');
    }

    public function isFix(): bool
    {
        return $this->type === ChangelogEntryTypeEnum::Fix;
    }
}
