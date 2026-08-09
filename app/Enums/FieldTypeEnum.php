<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The kinds of field a section can hold.
 *
 * These are the ones the first version ships with. The catalogue names more
 * (tags, duration, money, a full name, a rating, rich text, a colour, a
 * location, a computed value, a qualified relation), and each of them arrives
 * with the storage and the rendering it needs rather than as a string somebody
 * can already write into the column.
 *
 * The rule that makes the catalogue worth having is that a type renders
 * accessibly by construction: the label bound to the input, the error
 * announced, the required state programmatic and the whole thing usable with a
 * keyboard alone. That is implemented once per type, here, and not checked
 * again for every template anybody builds.
 */
enum FieldTypeEnum: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Boolean = 'boolean';
    case Url = 'url';
    case Email = 'email';
    case Phone = 'phone';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Radio = 'radio';
    case Date = 'date';
    case RecurringDate = 'recurring_date';
    case Relation = 'relation';
    case File = 'file';
    case Image = 'image';
    case Avatar = 'avatar';
    case Address = 'address';
    case Repeater = 'repeater';
    case Divider = 'divider';
    case Heading = 'heading';
    case Help = 'help';

    /**
     * Whether anything is ever stored for this field. The layout types are
     * there to break a long page up, and they carry no value row, which is also
     * what makes them invisible to search, to export and to a share link.
     */
    public function storesValue(): bool
    {
        return ! in_array($this, [self::Divider, self::Heading, self::Help], true);
    }

    /**
     * Whether the field draws its choices from a value list, which is what
     * spares somebody retyping the same handful of options into every field
     * that offers them.
     */
    public function usesValueList(): bool
    {
        return in_array($this, [self::Select, self::Multiselect, self::Radio], true);
    }

    /**
     * Whether the field points at another record, and therefore has to say what
     * kind of record it points at.
     */
    public function isRelation(): bool
    {
        return $this === self::Relation;
    }

    /**
     * Whether a reminder rule may read from this field. Any recurring date can,
     * not only a birthday: an anniversary, the date a visa expires, the month a
     * subscription renews. Nothing in the reminder machinery is allowed to
     * special case one of them.
     */
    public function canDriveAReminder(): bool
    {
        return $this === self::RecurringDate;
    }

    /**
     * Whether the field is a section that can be filled several times over, which
     * is the one type other fields may sit inside.
     */
    public function isRepeater(): bool
    {
        return $this === self::Repeater;
    }
}
