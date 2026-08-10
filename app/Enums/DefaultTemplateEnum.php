<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;

/**
 * The templates an account is created with.
 *
 * There is one, and it is deliberately conservative: a name and an avatar stay
 * core properties of a contact and are not asked for twice, and nothing that
 * needs a screen the builder does not have yet appears on it. What it is for is
 * to be useful in the first minute and to show what the personalisation engine
 * does, not to guess at what somebody keeps about the people they know.
 *
 * The backing value is the `slug` column of the template.
 */
enum DefaultTemplateEnum: string implements Translatable
{
    case BasicContact = 'basic-contact';

    /**
     * What the template is called. The name doubles as the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::BasicContact => 'Basic contact',
        };
    }

    /**
     * What kind of record the template describes.
     */
    public function appliesTo(): TemplateAppliesToEnum
    {
        return match ($this) {
            self::BasicContact => TemplateAppliesToEnum::Contact,
        };
    }

    /**
     * The tabs the template is read in, in order. What goes on each of them is
     * the page's own business.
     *
     * @return list<DefaultPageEnum>
     */
    public function pages(): array
    {
        return match ($this) {
            self::BasicContact => DefaultPageEnum::cases(),
        };
    }

    /**
     * The name this template is seeded with. Its pages, sections and fields
     * carry their own.
     *
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->label()];
    }
}
