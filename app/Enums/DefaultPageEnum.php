<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use App\Models\StructureVersionPage;
use Override;

enum DefaultPageEnum: string implements Translatable
{
    case General = 'general';
    case WaysToConnect = 'ways-to-connect';
    case Information = 'information';
    case AnimalGeneral = 'animal-general';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::WaysToConnect => 'Ways to connect',
            self::Information => 'Information',
            self::AnimalGeneral => 'General',
        };
    }

    /**
     * The key the page is stored under. A template with one page calls it general, whatever the
     * enum case has to be called to stay apart from the pages of Basic contact.
     */
    public function key(): string
    {
        return match ($this) {
            self::AnimalGeneral => StructureVersionPage::SUPPLIED_KEY,
            default => $this->value,
        };
    }

    /** @return list<DefaultSectionEnum> */
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
            self::AnimalGeneral => [
                DefaultSectionEnum::AnimalDetails,
                DefaultSectionEnum::AnimalPeople,
                DefaultSectionEnum::AnimalCare,
                DefaultSectionEnum::AnimalNotes,
            ],
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
