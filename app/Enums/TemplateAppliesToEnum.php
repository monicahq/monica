<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What kind of record a template describes.
 *
 * The specification names a third case, an activity of a given nature, which is
 * missing here on purpose: an activity template is reached from the activity
 * type rather than from the template, and that model does not exist yet. A
 * template that applies to nothing readable is a template nobody can use, so
 * the case arrives with the model that reads it.
 */
enum TemplateAppliesToEnum: string
{
    case Contact = 'contact';
    case Entity = 'entity';

    /**
     * Whether a template of this kind has to name the type it applies to. A
     * contact is one thing and needs no further pointing; an entity is whatever
     * the account invented, so the type is what says which one.
     */
    public function needsEntityType(): bool
    {
        return $this === self::Entity;
    }
}
