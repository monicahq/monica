<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\Group;
use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;

/**
 * One date of a vault: a deadline of a reminder, or a birthday nobody asked to be reminded of, which
 * carries no occurrence and so nothing to answer.
 */
class VaultReminderDate
{
    public function __construct(
        public readonly ?ReminderOccurrence $occurrence,
        public readonly Contact|Group|Entity|null $subject,
        public readonly CarbonImmutable $date,
        public readonly ?int $age,
    ) {}
}
