<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The three states a version of a template or of a section can be in.
 *
 * Both kinds of version follow the same rules, so they share one enum rather
 * than two identical ones. A draft is freely editable, because freezing at
 * every keystroke during design would produce hundreds of versions nobody asked
 * for. Publishing is what freezes it, and it is final: a published version
 * never changes again, and an edit produces a new version beside it.
 *
 * Retiring is not deleting. It stops the version being offered to anything new
 * and leaves every record already on it exactly as it is.
 */
enum VersionStatusEnum: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Retired = 'retired';

    /**
     * Whether what the version contains may still be changed. Only a draft may:
     * everything the personalisation engine promises about records staying
     * stable rests on this being false once a version has been published.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Whether the version may still be applied to a new record. A retired one
     * may not, and neither may a draft, which nothing outside the builder has
     * any business pointing at.
     */
    public function isOffered(): bool
    {
        return $this === self::Published;
    }
}
