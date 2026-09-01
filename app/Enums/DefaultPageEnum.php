<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

/**
 * The pages the template an account starts with is read in.
 *
 * The backing value is the `key` column of the page, which is what a record URL
 * carries and what every later draft keeps. The order of the cases is the order
 * of the tabs.
 *
 * Three of them rather than one long screen, and three rather than the dozen a
 * contact page eventually holds: a page is seeded only when there is something
 * to put on it. Relationships, pets, groups, life events, goals, reminders,
 * loans, tasks, calls and journal entries are each their own model with their
 * own screens, none of which exists, and a tab named after one of them would be
 * a tab that opens on nothing.
 */
enum DefaultPageEnum: string implements Translatable
{
    case General = 'general';
    case WaysToConnect = 'ways-to-connect';
    case Information = 'information';

    /**
     * What the tab says. The label doubles as the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::WaysToConnect => 'Ways to connect',
            self::Information => 'Information',
        };
    }

    /**
     * What the page holds, in the order it is read in.
     *
     * @return list<DefaultSectionEnum>
     */
    public function sections(): array
    {
        return match ($this) {
            self::General => [
                DefaultSectionEnum::PersonalDetails,
                DefaultSectionEnum::Notes,
            ],
            self::WaysToConnect => [
                DefaultSectionEnum::ContactInformation,
                DefaultSectionEnum::Addresses,
            ],
            self::Information => [
                DefaultSectionEnum::Documents,
                DefaultSectionEnum::Photos,
            ],
        };
    }

    /**
     * The name this page is seeded with. Its sections carry their own.
     *
     * @return list<string>
     */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
