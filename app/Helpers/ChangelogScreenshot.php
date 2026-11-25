<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * One screenshot of a changelog entry. Its file is named after the entry id and its position, so
 * it survives a rename of the entry's file, and its description is an English translation key.
 */
readonly class ChangelogScreenshot
{
    public function __construct(
        public int $position,
        public string $description,
        public string $file,
        public int $width,
        public int $height,
    ) {}

    public function url(): string
    {
        return asset("images/changelog/{$this->file}");
    }
}
