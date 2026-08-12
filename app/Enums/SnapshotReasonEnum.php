<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Why the state of a record was captured.
 *
 * The reason is on the row rather than inferred, because a snapshot is offered
 * back to somebody as a moment to return to, and "before you changed the
 * structure" and "you asked for this one" are not the same offer. It is also
 * what a retention rule will read: the snapshot taken before a structure change
 * is the one the product promised, and it cannot be pruned like the rest.
 */
enum SnapshotReasonEnum: string
{
    case TemplateChange = 'template_change';
    case Merge = 'merge';
    case Manual = 'manual';
    case Import = 'import';
    case Restore = 'restore';
    case Api = 'api';

    /**
     * Whether the product took this one on somebody's behalf rather than
     * because they asked for it.
     */
    public function isAutomatic(): bool
    {
        return in_array($this, [self::TemplateChange, self::Merge, self::Import, self::Restore], true);
    }
}
