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
    /**
     * What the first page of a template version is called, whoever created the
     * template. It sits here rather than beside the model because this is where
     * the words the product supplies live, and where `monica:localize` looks
     * for them. Renaming the page replaces it with the user's own word.
     */
    public const string PAGE_LABEL = 'General';

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
     * What the template is assembled from, in the order it is read in.
     *
     * @return list<DefaultSectionEnum>
     */
    public function sections(): array
    {
        return match ($this) {
            self::BasicContact => DefaultSectionEnum::cases(),
        };
    }

    /**
     * The name this template is seeded with. Its sections carry their own.
     *
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->label(), self::PAGE_LABEL];
    }
}
